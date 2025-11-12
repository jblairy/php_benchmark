<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Command;

use Exception;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\BenchmarkFileResolver;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\CalibrationProgressTracker;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\CalibrationResultFormatter;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\CalibrationService;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\ValueObject\CalibrationOptions;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\YamlFileManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

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
final readonly class CalibrateIterationsCommand
{
    private const float DEFAULT_TARGET_TIME_MS = 1000.0;

    public function __construct(
        private BenchmarkFileResolver $fileResolver,
        private YamlFileManager $yamlFileManager,
        private CalibrationService $calibrationService,
    ) {
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

        $options = CalibrationOptions::fromFlags($dryRun, $force);
        $formatter = new CalibrationResultFormatter($symfonyStyle);
        $tracker = new CalibrationProgressTracker($symfonyStyle);

        $formatter->displayHeader($targetTime, $phpVersion, $options->isDryRun);

        $benchmarkFiles = $this->fileResolver->resolveBenchmarkFiles($benchmark, $all, $symfonyStyle);
        if (null === $benchmarkFiles) {
            return Command::FAILURE;
        }

        $this->calibrateBenchmarks($benchmarkFiles, $targetTime, $options, $tracker);

        $results = $tracker->getResults();
        $formatter->displayResults($results['results'], $results['errors'], $results['skipped'], $options->isDryRun);

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $benchmarkFiles
     */
    private function calibrateBenchmarks(
        array $benchmarkFiles,
        float $targetTime,
        CalibrationOptions $options,
        CalibrationProgressTracker $tracker,
    ): void {
        $tracker->startProgress(count($benchmarkFiles));

        foreach ($benchmarkFiles as $benchmarkFile) {
            $tracker->advanceProgress();
            $this->calibrateSingleBenchmark($benchmarkFile, $targetTime, $options, $tracker);
        }

        $tracker->finishProgress();
    }

    private function calibrateSingleBenchmark(
        string $benchmarkFile,
        float $targetTime,
        CalibrationOptions $options,
        CalibrationProgressTracker $tracker,
    ): void {
        try {
            $data = $this->yamlFileManager->readBenchmarkData($benchmarkFile);
            if (null === $data) {
                $tracker->trackSkipped();

                return;
            }

            if ($this->calibrationService->shouldSkipBenchmark($data, $options)) {
                $tracker->trackSkipped();

                return;
            }

            $calibration = $this->calibrationService->calibrateBenchmark($data, $targetTime);

            if (null === $calibration) {
                $tracker->trackSkipped();

                return;
            }

            if ($options->shouldUpdateFixtures()) {
                $this->yamlFileManager->updateBenchmarkIterations(
                    $benchmarkFile,
                    $calibration->suggestedWarmup,
                    $calibration->suggestedInner,
                );
            }

            $tracker->trackCalibration($calibration);
        } catch (Exception $e) {
            $tracker->trackError(basename($benchmarkFile), $e->getMessage());
        }
    }
}
