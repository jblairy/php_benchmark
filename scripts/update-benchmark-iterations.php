<?php

declare(strict_types=1);

/**
 * Script to update benchmark fixtures with optimal iteration values.
 * 
 * Usage: php scripts/update-benchmark-iterations.php [--dry-run]
 */

use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/../vendor/autoload.php';

$dryRun = in_array('--dry-run', $argv ?? []);
$fixturesPath = __DIR__ . '/../fixtures/benchmarks';
$files = glob($fixturesPath . '/*.yaml');

echo "Updating " . count($files) . " benchmark fixtures...\n";
if ($dryRun) {
    echo "(DRY RUN - No files will be modified)\n";
}
echo "\n";

$stats = [
    'total' => count($files),
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0,
];

// Heavy operations that require reduced iterations
$heavyOperations = [
    'mb_' => ['weight' => 3.0, 'suggested' => ['warmup' => 3, 'inner' => 20]],
    'preg_' => ['weight' => 4.0, 'suggested' => ['warmup' => 3, 'inner' => 20]],
    'hash(' => ['weight' => 3.0, 'suggested' => ['warmup' => 5, 'inner' => 50]],
    'crypt(' => ['weight' => 5.0, 'suggested' => ['warmup' => 3, 'inner' => 20]],
    'serialize(' => ['weight' => 2.5, 'suggested' => ['warmup' => 5, 'inner' => 50]],
    'json_encode(' => ['weight' => 2.0, 'suggested' => ['warmup' => 10, 'inner' => 100]],
    'json_decode(' => ['weight' => 2.5, 'suggested' => ['warmup' => 5, 'inner' => 50]],
];

// Special categories
$categorySettings = [
    'Iteration' => ['warmup' => 5, 'inner' => 10], // Loop benchmarks
    'Loop' => ['warmup' => 5, 'inner' => 10],
];

foreach ($files as $file) {
    try {
        $data = Yaml::parseFile($file);
        $slug = $data['slug'] ?? basename($file, '.yaml');
        
        // Skip if already has custom iterations
        if (isset($data['warmupIterations']) || isset($data['innerIterations'])) {
            echo sprintf("SKIP  %-40s (already configured)\n", $slug);
            $stats['skipped']++;
            continue;
        }
        
        $code = $data['code'] ?? '';
        $category = $data['category'] ?? '';
        
        // Check if it's a loop benchmark
        if (isset($categorySettings[$category])) {
            $warmup = $categorySettings[$category]['warmup'];
            $inner = $categorySettings[$category]['inner'];
            $reason = "category:$category";
        } else {
            // Extract iteration count from code
            $iterations = 1;
            if (preg_match('/for\s*\([^;]+;\s*(\d+)\s*>/', $code, $matches)) {
                $iterations = (int) $matches[1];
            }
            
            // Check for heavy operations
            $heavyOp = null;
            foreach ($heavyOperations as $op => $config) {
                if (str_contains($code, $op)) {
                    $heavyOp = $op;
                    $warmup = $config['suggested']['warmup'];
                    $inner = $config['suggested']['inner'];
                    $reason = "heavy:$op";
                    break;
                }
            }
            
            if (!$heavyOp) {
                // Calculate based on complexity
                $complexity = log10($iterations + 1);
                
                if ($complexity >= 5) {
                    $warmup = 5;
                    $inner = 50;
                    $reason = "complex:$complexity";
                } elseif ($complexity >= 4) {
                    $warmup = 10;
                    $inner = 100;
                    $reason = "moderate:$complexity";
                } else {
                    // Default values are fine
                    echo sprintf("SKIP  %-40s (default values OK)\n", $slug);
                    $stats['skipped']++;
                    continue;
                }
            }
        }
        
        // Update the data
        $data['warmupIterations'] = $warmup;
        $data['innerIterations'] = $inner;
        
        if (!$dryRun) {
            // Preserve formatting as much as possible
            $yaml = Yaml::dump($data, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            file_put_contents($file, $yaml);
        }
        
        echo sprintf(
            "UPDATE %-40s warmup=%2d inner=%4d (%s)\n",
            $slug,
            $warmup,
            $inner,
            $reason
        );
        $stats['updated']++;
        
    } catch (Exception $e) {
        echo sprintf("ERROR %-40s %s\n", basename($file), $e->getMessage());
        $stats['errors']++;
    }
}

echo "\n";
echo "Summary:\n";
echo "  Total files:  {$stats['total']}\n";
echo "  Updated:      {$stats['updated']}\n";
echo "  Skipped:      {$stats['skipped']}\n";
echo "  Errors:       {$stats['errors']}\n";

if ($dryRun) {
    echo "\n";
    echo "This was a DRY RUN. To apply changes, run without --dry-run flag.\n";
}