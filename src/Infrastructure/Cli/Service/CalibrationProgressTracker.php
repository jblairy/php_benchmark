<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\ValueObject\CalibrationResult;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Tracks calibration progress and aggregates results.
 */
final class CalibrationProgressTracker
{
    /**
     * @var list<CalibrationResult>
     */
    private array $results = [];

    /**
     * @var list<array{benchmark: string, error: string}>
     */
    private array $errors = [];

    private int $skipped = 0;

    public function __construct(private readonly SymfonyStyle $symfonyStyle)
    {
    }

    public function startProgress(int $total): void
    {
        $this->symfonyStyle->progressStart($total);
    }

    public function advanceProgress(): void
    {
        $this->symfonyStyle->progressAdvance();
    }

    public function finishProgress(): void
    {
        $this->symfonyStyle->progressFinish();
        $this->symfonyStyle->newLine();
    }

    public function trackCalibration(CalibrationResult $result): void
    {
        $this->results[] = $result;
    }

    public function trackError(string $benchmark, string $error): void
    {
        $this->errors[] = ['benchmark' => $benchmark, 'error' => $error];
    }

    public function trackSkipped(): void
    {
        ++$this->skipped;
    }

    /**
     * @return array{results: list<CalibrationResult>, errors: list<array{benchmark: string, error: string}>, skipped: int}
     */
    public function getResults(): array
    {
        return [
            'results' => $this->results,
            'errors' => $this->errors,
            'skipped' => $this->skipped,
        ];
    }
}
