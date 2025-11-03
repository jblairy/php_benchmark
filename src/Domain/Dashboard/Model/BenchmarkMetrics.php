<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Model;

/**
 * Value Object representing raw benchmark metrics before statistical analysis.
 */
final readonly class BenchmarkMetrics
{
    /**
     * @param array<int, float> $executionTimes
     * @param array<int, float> $memoryUsages
     * @param array<int, float> $memoryPeaks
     */
    public function __construct(
        public string $benchmarkId,
        public string $benchmarkName,
        public string $phpVersion,
        public array $executionTimes,
        public array $memoryUsages,
        public array $memoryPeaks,
    ) {
    }

    public function getExecutionCount(): int
    {
        return count($this->executionTimes);
    }

    public function isEmpty(): bool
    {
        return 0 === $this->getExecutionCount();
    }
}
