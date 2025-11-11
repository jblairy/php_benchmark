<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Model;

/**
 * Value Object representing benchmark statistics for a specific PHP version.
 * 
 * Note: Not final to allow EnhancedBenchmarkStatistics to extend with outlier detection metrics.
 */
readonly class BenchmarkStatistics
{
    public function __construct(
        public string $benchmarkId,
        public string $benchmarkName,
        public string $phpVersion,
        public int $executionCount,
        public float $averageExecutionTime,
        public PercentileMetrics $percentiles,
        public float $averageMemoryUsed,
        public float $peakMemoryUsed,
        public float $minExecutionTime,
        public float $maxExecutionTime,
        public float $standardDeviation,
        public float $coefficientOfVariation,
        public float $throughput,
    ) {
    }
}
