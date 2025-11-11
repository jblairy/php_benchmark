<?php

declare(strict_types=1);

/**
 * Script to analyze benchmarks and suggest optimal iteration values.
 *
 * Usage: php scripts/analyze-benchmark-iterations.php
 */

use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/../vendor/autoload.php';

$fixturesPath = __DIR__ . '/../fixtures/benchmarks';
$files = glob($fixturesPath . '/*.yaml');

echo 'Analyzing ' . count($files) . " benchmark fixtures...\n\n";

$categories = [];
$suggestions = [];

foreach ($files as $file) {
    $data = Yaml::parseFile($file);
    $code = $data['code'] ?? '';

    // Extract iteration count
    $iterations = 1;
    if (preg_match('/for\s*\([^;]+;\s*(\d+)\s*>/', $code, $matches)) {
        $iterations = (int) $matches[1];
    }

    // Analyze operations
    $heavyOperations = [
        'mb_' => 3.0,
        'preg_' => 4.0,
        'hash(' => 3.0,
        'crypt(' => 5.0,
        'serialize(' => 2.5,
        'json_encode(' => 2.0,
        'json_decode(' => 2.5,
        'array_merge(' => 2.0,
        'str_replace(' => 1.5,
        'file_' => 10.0,
        'curl_' => 10.0,
    ];

    $operationWeight = 1.0;
    $detectedOps = [];

    foreach ($heavyOperations as $op => $weight) {
        if (str_contains($code, $op)) {
            $operationWeight = max($operationWeight, $weight);
            $detectedOps[] = $op;
        }
    }

    // Calculate complexity score
    $complexity = log10($iterations + 1) * $operationWeight;

    // Suggest iterations
    if (15 <= $complexity) {
        $warmup = 3;
        $inner = 20;
    } elseif (10 <= $complexity) {
        $warmup = 5;
        $inner = 50;
    } elseif (5 <= $complexity) {
        $warmup = 10;
        $inner = 100;
    } elseif (2 <= $complexity) {
        $warmup = 15;
        $inner = 200;
    } else {
        $warmup = 20;
        $inner = 500;
    }

    // Special cases
    $category = $data['category'] ?? 'Unknown';

    // For iteration/loop benchmarks, use minimal inner iterations
    if (in_array($category, ['Iteration', 'Loop'], true) || str_contains($data['name'] ?? '', 'Loop')) {
        $warmup = 5;
        $inner = 10; // Minimal because the loop IS the test
    }

    $suggestions[$data['slug']] = [
        'name' => $data['name'],
        'category' => $category,
        'fixture_iterations' => $iterations,
        'complexity_score' => round($complexity, 2),
        'operations' => $detectedOps,
        'suggested_warmup' => $warmup,
        'suggested_inner' => $inner,
        'estimated_total_ops' => $iterations * $inner,
    ];

    $categories[$category][] = $data['slug'];
}

// Display analysis
echo "=== COMPLEXITY DISTRIBUTION ===\n";
$complexityGroups = [
    'Extreme (>15)' => 0,
    'Heavy (10-15)' => 0,
    'Moderate (5-10)' => 0,
    'Light (2-5)' => 0,
    'Minimal (<2)' => 0,
];

foreach ($suggestions as $s) {
    if (15 <= $s['complexity_score']) {
        ++$complexityGroups['Extreme (>15)'];
    } elseif (10 <= $s['complexity_score']) {
        ++$complexityGroups['Heavy (10-15)'];
    } elseif (5 <= $s['complexity_score']) {
        ++$complexityGroups['Moderate (5-10)'];
    } elseif (2 <= $s['complexity_score']) {
        ++$complexityGroups['Light (2-5)'];
    } else {
        ++$complexityGroups['Minimal (<2)'];
    }
}

foreach ($complexityGroups as $group => $count) {
    echo sprintf("%-20s: %3d benchmarks\n", $group, $count);
}

echo "\n=== HEAVY BENCHMARKS (need reduced iterations) ===\n";
$heavy = array_filter($suggestions, fn ($s) => 10 <= $s['complexity_score']);
uasort($heavy, fn ($a, $b) => $b['complexity_score'] <=> $a['complexity_score']);

foreach (array_slice($heavy, 0, 10) as $slug => $s) {
    echo sprintf(
        "%-30s: complexity=%.1f, ops=%s, suggest: warmup=%d, inner=%d\n",
        $slug,
        $s['complexity_score'],
        implode(',', $s['operations']),
        $s['suggested_warmup'],
        $s['suggested_inner'],
    );
}

echo "\n=== CATEGORY ANALYSIS ===\n";
foreach ($categories as $category => $benchmarks) {
    $avgComplexity = array_sum(array_map(
        fn ($slug) => $suggestions[$slug]['complexity_score'],
        $benchmarks,
    )) / count($benchmarks);

    echo sprintf(
        "%-20s: %3d benchmarks, avg complexity: %.1f\n",
        $category,
        count($benchmarks),
        $avgComplexity,
    );
}

// Generate YAML updates
echo "\n=== GENERATING UPDATE SUGGESTIONS ===\n";
$updates = [];
foreach ($suggestions as $slug => $s) {
    if (10 !== $s['suggested_warmup'] || 100 !== $s['suggested_inner']) {
        $updates[] = sprintf(
            '%-40s: warmupIterations: %d, innerIterations: %d',
            $slug . ':',
            $s['suggested_warmup'],
            $s['suggested_inner'],
        );
    }
}

echo 'Found ' . count($updates) . " benchmarks needing custom iterations.\n";
echo "\nAdd these to your fixtures:\n\n";
foreach (array_slice($updates, 0, 20) as $update) {
    echo $update . "\n";
}
