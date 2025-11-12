<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\ValueObject\CalibrationResult;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Formats and displays calibration results.
 */
final readonly class CalibrationResultFormatter
{
    public function __construct(private SymfonyStyle $symfonyStyle)
    {
    }

    public function displayHeader(float $targetTime, string $phpVersion, bool $dryRun): void
    {
        $this->symfonyStyle->title('Benchmark Iteration Calibration');
        $this->symfonyStyle->info(sprintf('Target execution time: %.0f ms', $targetTime));
        $this->symfonyStyle->info(sprintf('Calibration PHP version: %s', $phpVersion));

        if ($dryRun) {
            $this->symfonyStyle->warning('DRY RUN MODE - No files will be modified');
        }
    }

    /**
     * @param list<CalibrationResult>                       $results
     * @param list<array{benchmark: string, error: string}> $errors
     */
    public function displayResults(array $results, array $errors, int $skipped, bool $dryRun): void
    {
        if ([] !== $results) {
            $this->displaySuccessfulCalibrations($results);
        }

        if (0 < $skipped) {
            $this->symfonyStyle->info(sprintf('Skipped %d benchmarks (already configured or loop tests)', $skipped));
        }

        if ([] !== $errors) {
            $this->displayErrors($errors);
        }

        if ($dryRun && [] !== $results) {
            $this->symfonyStyle->note('This was a DRY RUN. Run without --dry-run to apply changes.');
        }
    }

    /**
     * @param list<CalibrationResult> $results
     */
    private function displaySuccessfulCalibrations(array $results): void
    {
        $this->symfonyStyle->section('Calibration Results');

        $table = [];
        foreach ($results as $result) {
            $table[] = [
                $result->benchmark,
                sprintf('%.2f ms', $result->measuredTime),
                $result->suggestedWarmup,
                $result->suggestedInner,
                sprintf('%.0f%%', $result->efficiency),
            ];
        }

        $this->symfonyStyle->table(
            ['Benchmark', 'Measured Time', 'Warmup', 'Inner', 'Efficiency'],
            $table,
        );

        $this->symfonyStyle->success(sprintf('Calibrated %d benchmarks', count($results)));
    }

    /**
     * @param list<array{benchmark: string, error: string}> $errors
     */
    private function displayErrors(array $errors): void
    {
        $this->symfonyStyle->section('Errors');
        foreach ($errors as $error) {
            $this->symfonyStyle->error(sprintf('%s: %s', $error['benchmark'], $error['error']));
        }
    }
}
