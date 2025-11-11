<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Command;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
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
final class CalibrateIterationsCommand
{
    private const float DEFAULT_TARGET_TIME_MS = 1000.0;
    // 1 second
    private const int MIN_INNER = 10;

    private const int MAX_INNER = 1000;

    public function __construct(private readonly string $projectDir)
    {
    }

    public function __invoke(
        #[\Symfony\Component\Console\Attribute\Option(name: 'benchmark', shortcut: 'b', description: 'Benchmark slug to calibrate')]
        ?string $benchmark = null,
        #[\Symfony\Component\Console\Attribute\Option(name: 'all', shortcut: 'a', description: 'Calibrate all benchmarks')]
        bool $all = false,
        #[\Symfony\Component\Console\Attribute\Option(name: 'target-time', shortcut: 't', description: 'Target execution time in milliseconds')]
        float $targetTime = self::DEFAULT_TARGET_TIME_MS,
        #[\Symfony\Component\Console\Attribute\Option(name: 'php-version', shortcut: 'p', description: 'PHP version to use for calibration')]
        string $phpVersion = 'php56',
        #[\Symfony\Component\Console\Attribute\Option(name: 'dry-run', description: 'Show suggestions without updating fixtures')]
        bool $dryRun = false,
        #[\Symfony\Component\Console\Attribute\Option(name: 'force', shortcut: 'f', description: 'Update fixtures even if already configured')]
        bool $force = false,
        ?SymfonyStyle $symfonyStyle = null,
    ): int {
        // Guard against null $symfonyStyle - should not happen in normal execution
        if (null === $symfonyStyle) {
            return Command::FAILURE;
        }

        // Variables are already correctly typed from function parameters
        // No need for additional assignments
        $symfonyStyle->title('Benchmark Iteration Calibration');
        $symfonyStyle->info(sprintf('Target execution time: %.0f ms', $targetTime));
        $symfonyStyle->info(sprintf('Calibration PHP version: %s', $phpVersion));
        if ($dryRun) {
            $symfonyStyle->warning('DRY RUN MODE - No files will be modified');
        }
        // Get fixtures path
        $fixturesPath = $this->projectDir . '/fixtures/benchmarks';
        if (!is_dir($fixturesPath)) {
            $symfonyStyle->error('Fixtures directory not found: ' . $fixturesPath);

            return Command::FAILURE;
        }
        // Get benchmarks to calibrate
        /** @var list<string> $benchmarkFiles */
        $benchmarkFiles = [];
        if ($all) {
            $globResult = glob($fixturesPath . '/*.yaml');
            if (false === $globResult) {
                $symfonyStyle->error('Failed to read fixtures directory');

                return Command::FAILURE;
            }

            $benchmarkFiles = $globResult;
            $symfonyStyle->info(sprintf('Calibrating %d benchmarks...', count($benchmarkFiles)));
        } else {
            $benchmarkSlugOption = $benchmark;
            if (!is_string($benchmarkSlugOption)) {
                $symfonyStyle->error('Please specify --benchmark=<slug> or --all');

                return Command::FAILURE;
            }

            $file = $fixturesPath . '/' . $benchmarkSlugOption . '.yaml';
            if (!file_exists($file)) {
                $symfonyStyle->error(sprintf('Benchmark not found: %s', $benchmarkSlugOption));

                return Command::FAILURE;
            }

            $benchmarkFiles = [$file];
        }
        $symfonyStyle->progressStart(count($benchmarkFiles));
        $results = [];
        $errors = [];
        $skipped = 0;
        foreach ($benchmarkFiles as $benchmarkFile) {
            $symfonyStyle->progressAdvance();

            try {
                $data = Yaml::parseFile($benchmarkFile);
                if (!is_array($data)) {
                    ++$skipped;

                    continue;
                }

                $slug = is_string($data['slug'] ?? null) ? $data['slug'] : basename($benchmarkFile, '.yaml');

                // Skip if already configured and not forced
                if (!$force && (isset($data['warmupIterations']) || isset($data['innerIterations']))) {
                    ++$skipped;

                    continue;
                }

                // Skip loop/iteration benchmarks
                $category = is_string($data['category'] ?? null) ? $data['category'] : '';
                if (in_array($category, ['Iteration', 'Loop'], true)) {
                    ++$skipped;

                    continue;
                }

                $calibration = $this->calibrateBenchmark($data, $targetTime);

                if (null !== $calibration) {
                    $results[] = $calibration;

                    if (!$dryRun) {
                        $this->updateFixture($benchmarkFile, $calibration);
                    }
                }
            } catch (Exception $e) {
                $errors[] = [
                    'benchmark' => basename($benchmarkFile),
                    'error' => $e->getMessage(),
                ];
            }
        }
        $symfonyStyle->progressFinish();
        $symfonyStyle->newLine();
        // Display results
        if ([] !== $results) {
            $symfonyStyle->section('Calibration Results');

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

            $symfonyStyle->table(
                ['Benchmark', 'Measured Time', 'Warmup', 'Inner', 'Efficiency'],
                $table,
            );

            $symfonyStyle->success(sprintf('Calibrated %d benchmarks', count($results)));
        }
        if (0 < $skipped) {
            $symfonyStyle->info(sprintf('Skipped %d benchmarks (already configured or loop tests)', $skipped));
        }
        // Display errors
        if ([] !== $errors) {
            $symfonyStyle->section('Errors');
            foreach ($errors as $error) {
                $symfonyStyle->error(sprintf('%s: %s', $error['benchmark'], $error['error']));
            }
        }
        if ($dryRun && [] !== $results) {
            $symfonyStyle->note('This was a DRY RUN. Run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<mixed> $benchmarkData
     *
     * @return array{benchmark: string, measured_time: float, suggested_warmup: int, suggested_inner: int, efficiency: float}|null
     */
    private function calibrateBenchmark(array $benchmarkData, float $targetTimeMs): ?array
    {
        $code = is_string($benchmarkData['code'] ?? null) ? $benchmarkData['code'] : '';
        $slug = is_string($benchmarkData['slug'] ?? null) ? $benchmarkData['slug'] : 'unknown';

        if ('' === $code) {
            return null;
        }

        // Measure single execution (code already contains loops)
        $measuredTime = $this->measureExecutionTime($code);

        if (null === $measuredTime || 0 >= $measuredTime) {
            return null;
        }

        // Calculate optimal inner iterations based on measured time
        // Inner iterations multiply the execution time
        $optimalInner = (int) ($targetTimeMs / $measuredTime);

        // Clamp to reasonable values
        $suggestedInner = max(self::MIN_INNER, min(self::MAX_INNER, $optimalInner));

        // Adjust warmup based on inner iterations and measured time
        $suggestedWarmup = match (true) {
            100 < $measuredTime => 1,  // Heavy benchmark
            50 < $measuredTime => 3,
            10 < $measuredTime => 5,
            1 < $measuredTime => 10,
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

    private function measureExecutionTime(string $code): ?float
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
            $output = shell_exec(sprintf('timeout 5s php %s 2>&1', $tempFile));
            @unlink($tempFile);

            if (null === $output || false === $output) {
                return null;
            }

            $trimmedOutput = mb_trim($output);
            if ('' === $trimmedOutput) {
                return null;
            }

            // Extract JSON from output (ignore PHP notices/warnings)
            $lines = explode("\n", $trimmedOutput);
            $jsonLine = null;
            foreach (array_reverse($lines) as $line) {
                assert(is_string($line));
                $trimmed = mb_trim($line);
                if ('' !== $trimmed && str_starts_with($trimmed, '{')) {
                    $jsonLine = $trimmed;

                    break;
                }
            }

            if (null === $jsonLine) {
                return null;
            }

            $result = json_decode($jsonLine, true);
            if (!is_array($result) || !isset($result['execution_time_ms'])) {
                return null;
            }

            $executionTime = $result['execution_time_ms'];
            if (!is_numeric($executionTime)) {
                return null;
            }

            return (float) $executionTime;
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @param array{benchmark: string, suggested_warmup: int, suggested_inner: int} $calibration
     */
    private function updateFixture(string $filename, array $calibration): void
    {
        $data = Yaml::parseFile($filename);
        if (!is_array($data)) {
            return;
        }

        $data['warmupIterations'] = $calibration['suggested_warmup'];
        $data['innerIterations'] = $calibration['suggested_inner'];

        $yaml = Yaml::dump($data, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        file_put_contents($filename, $yaml);
    }
}
