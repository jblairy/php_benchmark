<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Command;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
        if (null === $symfonyStyle) {
            return Command::FAILURE;
        }

        $this->displayHeader($symfonyStyle, $targetTime, $phpVersion, $dryRun);

        $benchmarkFiles = $this->resolveBenchmarkFiles($benchmark, $all, $symfonyStyle);
        if (null === $benchmarkFiles) {
            return Command::FAILURE;
        }

        $calibrationResults = $this->calibrateBenchmarks($benchmarkFiles, $targetTime, $dryRun, $force, $symfonyStyle);

        $this->displayResults($calibrationResults, $symfonyStyle, $dryRun);

        return Command::SUCCESS;
    }

    private function displayHeader(
        SymfonyStyle $symfonyStyle,
        float $targetTime,
        string $phpVersion,
        bool $dryRun,
    ): void {
        $symfonyStyle->title('Benchmark Iteration Calibration');
        $symfonyStyle->info(sprintf('Target execution time: %.0f ms', $targetTime));
        $symfonyStyle->info(sprintf('Calibration PHP version: %s', $phpVersion));

        if ($dryRun) {
            $symfonyStyle->warning('DRY RUN MODE - No files will be modified');
        }
    }

    /**
     * @return list<string>|null
     */
    private function resolveBenchmarkFiles(
        ?string $benchmark,
        bool $all,
        SymfonyStyle $symfonyStyle,
    ): ?array {
        $fixturesPath = $this->projectDir . '/fixtures/benchmarks';

        if (!is_dir($fixturesPath)) {
            $symfonyStyle->error('Fixtures directory not found: ' . $fixturesPath);

            return null;
        }

        if ($all) {
            return $this->getAllBenchmarkFiles($fixturesPath, $symfonyStyle);
        }

        return $this->getSingleBenchmarkFile($benchmark, $fixturesPath, $symfonyStyle);
    }

    /**
     * @return list<string>|null
     */
    private function getAllBenchmarkFiles(string $fixturesPath, SymfonyStyle $symfonyStyle): ?array
    {
        $globResult = glob($fixturesPath . '/*.yaml');
        if (false === $globResult) {
            $symfonyStyle->error('Failed to read fixtures directory');

            return null;
        }

        $symfonyStyle->info(sprintf('Calibrating %d benchmarks...', count($globResult)));

        return $globResult;
    }

    /**
     * @return list<string>|null
     */
    private function getSingleBenchmarkFile(
        ?string $benchmark,
        string $fixturesPath,
        SymfonyStyle $symfonyStyle,
    ): ?array {
        if (!is_string($benchmark)) {
            $symfonyStyle->error('Please specify --benchmark=<slug> or --all');

            return null;
        }

        $file = $fixturesPath . '/' . $benchmark . '.yaml';
        if (!file_exists($file)) {
            $symfonyStyle->error(sprintf('Benchmark not found: %s', $benchmark));

            return null;
        }

        return [$file];
    }

    /**
     * @param list<string> $benchmarkFiles
     *
     * @return array{results: list<array{benchmark: string, measured_time: float, suggested_warmup: int, suggested_inner: int, efficiency: float}>, errors: list<array{benchmark: string, error: string}>, skipped: int}
     */
    private function calibrateBenchmarks(
        array $benchmarkFiles,
        float $targetTime,
        bool $dryRun,
        bool $force,
        SymfonyStyle $symfonyStyle,
    ): array {
        $symfonyStyle->progressStart(count($benchmarkFiles));

        $results = [];
        $errors = [];
        $skipped = 0;

        foreach ($benchmarkFiles as $benchmarkFile) {
            $symfonyStyle->progressAdvance();

            $result = $this->calibrateSingleBenchmark($benchmarkFile, $targetTime, $dryRun, $force);

            if (null !== $result['error']) {
                $errors[] = $result['error'];
            } elseif ($result['skipped']) {
                ++$skipped;
            } elseif (null !== $result['calibration']) {
                $results[] = $result['calibration'];
            }
        }

        $symfonyStyle->progressFinish();
        $symfonyStyle->newLine();

        return compact('results', 'errors', 'skipped');
    }

    /**
     * @return array{calibration: array{benchmark: string, measured_time: float, suggested_warmup: int, suggested_inner: int, efficiency: float}|null, error: array{benchmark: string, error: string}|null, skipped: bool}
     */
    private function calibrateSingleBenchmark(
        string $benchmarkFile,
        float $targetTime,
        bool $dryRun,
        bool $force,
    ): array {
        try {
            $data = Yaml::parseFile($benchmarkFile);
            if (!is_array($data)) {
                return ['calibration' => null, 'error' => null, 'skipped' => true];
            }

            if ($this->shouldSkipBenchmark($data, $force)) {
                return ['calibration' => null, 'error' => null, 'skipped' => true];
            }

            $calibration = $this->calibrateBenchmark($data, $targetTime);

            if (null !== $calibration) {
                if (!$dryRun) {
                    $this->updateFixture($benchmarkFile, $calibration);
                }

                return ['calibration' => $calibration, 'error' => null, 'skipped' => false];
            }

            return ['calibration' => null, 'error' => null, 'skipped' => true];
        } catch (Exception $e) {
            return [
                'calibration' => null,
                'error' => [
                    'benchmark' => basename($benchmarkFile),
                    'error' => $e->getMessage(),
                ],
                'skipped' => false,
            ];
        }
    }

    /**
     * @param array<mixed> $data
     */
    private function shouldSkipBenchmark(array $data, bool $force): bool
    {
        if (!$force && (isset($data['warmupIterations']) || isset($data['innerIterations']))) {
            return true;
        }

        $category = is_string($data['category'] ?? null) ? $data['category'] : '';

        return in_array($category, ['Iteration', 'Loop'], true);
    }

    /**
     * @param array{results: list<array{benchmark: string, measured_time: float, suggested_warmup: int, suggested_inner: int, efficiency: float}>, errors: list<array{benchmark: string, error: string}>, skipped: int} $calibrationResults
     */
    private function displayResults(array $calibrationResults, SymfonyStyle $symfonyStyle, bool $dryRun): void
    {
        $results = $calibrationResults['results'];
        $errors = $calibrationResults['errors'];
        $skipped = $calibrationResults['skipped'];

        if ([] !== $results) {
            $this->displaySuccessfulCalibrations($results, $symfonyStyle);
        }

        if (0 < $skipped) {
            $symfonyStyle->info(sprintf('Skipped %d benchmarks (already configured or loop tests)', $skipped));
        }

        if ([] !== $errors) {
            $this->displayErrors($errors, $symfonyStyle);
        }

        if ($dryRun && [] !== $results) {
            $symfonyStyle->note('This was a DRY RUN. Run without --dry-run to apply changes.');
        }
    }

    /**
     * @param list<array{benchmark: string, measured_time: float, suggested_warmup: int, suggested_inner: int, efficiency: float}> $results
     */
    private function displaySuccessfulCalibrations(array $results, SymfonyStyle $symfonyStyle): void
    {
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

    /**
     * @param list<array{benchmark: string, error: string}> $errors
     */
    private function displayErrors(array $errors, SymfonyStyle $symfonyStyle): void
    {
        $symfonyStyle->section('Errors');
        foreach ($errors as $error) {
            $symfonyStyle->error(sprintf('%s: %s', $error['benchmark'], $error['error']));
        }
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

            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

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
