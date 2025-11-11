<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Command to calibrate benchmark iterations based on target execution time.
 * 
 * Measures actual execution time with minimal iterations and calculates
 * optimal values to achieve target time (default: 1 second).
 */
#[AsCommand(
    name: 'benchmark:calibrate',
    description: 'Calibrate benchmark iterations based on target execution time',
)]
final class CalibrateIterationsCommand extends Command
{
    private const float DEFAULT_TARGET_TIME_MS = 1000.0; // 1 second
    private const int MIN_WARMUP = 1;
    private const int MAX_WARMUP = 20;
    private const int MIN_INNER = 10;
    private const int MAX_INNER = 1000;
    
    public function __construct(
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('benchmark', 'b', InputOption::VALUE_REQUIRED, 'Benchmark slug to calibrate')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Calibrate all benchmarks')
            ->addOption('target-time', 't', InputOption::VALUE_REQUIRED, 'Target execution time in milliseconds', self::DEFAULT_TARGET_TIME_MS)
            ->addOption('php-version', 'p', InputOption::VALUE_REQUIRED, 'PHP version to use for calibration', 'php56')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show suggestions without updating fixtures')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Update fixtures even if already configured')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $targetTimeMs = (float) $input->getOption('target-time');
        $phpVersion = (string) $input->getOption('php-version');
        $dryRun = (bool) $input->getOption('dry-run');
        $force = (bool) $input->getOption('force');
        
        $io->title('Benchmark Iteration Calibration');
        $io->info(sprintf('Target execution time: %.0f ms', $targetTimeMs));
        $io->info(sprintf('Calibration PHP version: %s', $phpVersion));
        
        if ($dryRun) {
            $io->warning('DRY RUN MODE - No files will be modified');
        }

        // Get fixtures path
        $fixturesPath = $this->projectDir . '/fixtures/benchmarks';
        
        if (!is_dir($fixturesPath)) {
            $io->error('Fixtures directory not found: ' . $fixturesPath);
            return Command::FAILURE;
        }

        // Get benchmarks to calibrate
        $benchmarkFiles = [];
        if ($input->getOption('all')) {
            $benchmarkFiles = glob($fixturesPath . '/*.yaml');
            $io->info(sprintf('Calibrating %d benchmarks...', count($benchmarkFiles)));
        } elseif ($benchmarkSlug = $input->getOption('benchmark')) {
            $file = $fixturesPath . '/' . $benchmarkSlug . '.yaml';
            if (!file_exists($file)) {
                $io->error(sprintf('Benchmark not found: %s', $benchmarkSlug));
                return Command::FAILURE;
            }
            $benchmarkFiles = [$file];
        } else {
            $io->error('Please specify --benchmark=<slug> or --all');
            return Command::FAILURE;
        }

        $io->progressStart(count($benchmarkFiles));
        
        $results = [];
        $errors = [];
        $skipped = 0;

        foreach ($benchmarkFiles as $file) {
            $io->progressAdvance();
            
            try {
                $data = Yaml::parseFile($file);
                $slug = $data['slug'] ?? basename($file, '.yaml');
                
                // Skip if already configured and not forced
                if (!$force && (isset($data['warmupIterations']) || isset($data['innerIterations']))) {
                    $skipped++;
                    continue;
                }

                // Skip loop/iteration benchmarks
                $category = $data['category'] ?? '';
                if (in_array($category, ['Iteration', 'Loop'], true)) {
                    $skipped++;
                    continue;
                }

                $calibration = $this->calibrateBenchmark($data, $targetTimeMs);
                
                if ($calibration !== null) {
                    $results[] = $calibration;
                    
                    if (!$dryRun) {
                        $this->updateFixture($file, $calibration);
                    }
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'benchmark' => basename($file),
                    'error' => $e->getMessage(),
                ];
            }
        }

        $io->progressFinish();
        $io->newLine();

        // Display results
        if (count($results) > 0) {
            $io->section('Calibration Results');
            
            $table = [];
            foreach ($results as $result) {
                $table[] = [
                    $result['benchmark'],
                    sprintf('%.2f ms', $result['measured_time']),
                    $result['suggested_warmup'],
                    $result['suggested_inner'],
                    sprintf('%.0f%%', $result['efficiency']),
                ];
            }
            
            $io->table(
                ['Benchmark', 'Measured Time', 'Warmup', 'Inner', 'Efficiency'],
                $table
            );
            
            $io->success(sprintf('Calibrated %d benchmarks', count($results)));
        }

        if ($skipped > 0) {
            $io->info(sprintf('Skipped %d benchmarks (already configured or loop tests)', $skipped));
        }

        // Display errors
        if (count($errors) > 0) {
            $io->section('Errors');
            foreach ($errors as $error) {
                $io->error(sprintf('%s: %s', $error['benchmark'], $error['error']));
            }
        }

        if ($dryRun && count($results) > 0) {
            $io->note('This was a DRY RUN. Run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $benchmarkData
     * @return array{benchmark: string, measured_time: float, suggested_warmup: int, suggested_inner: int, efficiency: float}|null
     */
    private function calibrateBenchmark(array $benchmarkData, float $targetTimeMs): ?array
    {
        $code = $benchmarkData['code'] ?? '';
        $slug = $benchmarkData['slug'] ?? 'unknown';
        
        if (empty($code)) {
            return null;
        }

        // Measure single execution (code already contains loops)
        $measuredTime = $this->measureExecutionTime($code, 0, 0);
        
        if ($measuredTime === null || $measuredTime <= 0) {
            return null;
        }

        // Calculate optimal inner iterations based on measured time
        // Inner iterations multiply the execution time
        $optimalInner = (int) ($targetTimeMs / $measuredTime);
        
        // Clamp to reasonable values
        $suggestedInner = max(self::MIN_INNER, min(self::MAX_INNER, $optimalInner));
        
        // Adjust warmup based on inner iterations and measured time
        $suggestedWarmup = match (true) {
            $measuredTime > 100 => 1,  // Heavy benchmark
            $measuredTime > 50 => 3,
            $measuredTime > 10 => 5,
            $measuredTime > 1 => 10,
            default => 15,
        };
        
        // Calculate efficiency (how close we are to target)
        $projectedTime = $measuredTime * $suggestedInner;
        $efficiency = min(100, ($projectedTime / $targetTimeMs) * 100);

        return [
            'benchmark' => $slug,
            'measured_time' => $measuredTime,
            'suggested_warmup' => $suggestedWarmup,
            'suggested_inner' => $suggestedInner,
            'efficiency' => $efficiency,
        ];
    }

    private function measureExecutionTime(string $code, int $warmup, int $inner): ?float
    {
        try {
            // Simple approach: execute the code once and measure
            // The code in fixtures already contains loops, so we measure that
            $script = "<?php\n\n";
            $script .= "// Single execution measurement\n";
            $script .= "\$start = hrtime(true);\n";
            $script .= $code . "\n";
            $script .= "\$end = hrtime(true);\n";
            $script .= "\$elapsed_ms = (\$end - \$start) / 1_000_000;\n";
            $script .= "echo json_encode(['execution_time_ms' => \$elapsed_ms]);\n";

            // Create temporary file
            $tempFile = sys_get_temp_dir() . '/benchmark_calibration_' . uniqid() . '.php';
            file_put_contents($tempFile, $script);

            // Execute with PHP CLI with timeout
            $output = shell_exec("timeout 5s php {$tempFile} 2>&1");
            @unlink($tempFile);

            if ($output === null || trim($output) === '') {
                return null;
            }

            // Extract JSON from output (ignore PHP notices/warnings)
            $lines = explode("\n", trim($output));
            $jsonLine = null;
            foreach (array_reverse($lines) as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '' && str_starts_with($trimmed, '{')) {
                    $jsonLine = $trimmed;
                    break;
                }
            }

            if ($jsonLine === null) {
                return null;
            }

            $result = json_decode($jsonLine, true);
            if (!is_array($result) || !isset($result['execution_time_ms'])) {
                return null;
            }

            return (float) $result['execution_time_ms'];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param array{benchmark: string, suggested_warmup: int, suggested_inner: int} $calibration
     */
    private function updateFixture(string $filename, array $calibration): void
    {
        $data = Yaml::parseFile($filename);
        $data['warmupIterations'] = $calibration['suggested_warmup'];
        $data['innerIterations'] = $calibration['suggested_inner'];
        
        $yaml = Yaml::dump($data, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        file_put_contents($filename, $yaml);
    }
}