<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Model;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\BenchmarkIdentity;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\ExecutionMetrics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\MemoryMetrics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\StatisticalMetrics;

/**
 * Value Object representing benchmark statistics for a specific PHP version.
 *
 * Refactored to use Parameter Object pattern to reduce constructor complexity.
 * Previously had 13 constructor parameters, now has 4 cohesive parameter objects.
 *
 * Note: Not final to allow EnhancedBenchmarkStatistics to extend with outlier detection metrics.
 */
readonly class BenchmarkStatistics
{
    public function __construct(
        public BenchmarkIdentity $identity,
        public ExecutionMetrics $execution,
        public MemoryMetrics $memory,
        public StatisticalMetrics $statistics,
    ) {
    }

    /**
     * Factory method for creating statistics with individual parameters.
     * Useful for backward compatibility and simpler construction.
     *
     * @SuppressWarnings("PHPMD.ExcessiveParameterList") - Factory method with parameter objects would be less readable
     * @SuppressWarnings("PHPMD.StaticAccess") - Static factory method pattern is acceptable
     */
    public static function create(
        string $benchmarkId,
        string $benchmarkName,
        string $phpVersion,
        float $averageExecutionTime,
        float $minExecutionTime,
        float $maxExecutionTime,
        int $executionCount,
        float $throughput,
        float $averageMemoryUsed,
        float $peakMemoryUsed,
        float $standardDeviation,
        float $coefficientOfVariation,
        PercentileMetrics $percentiles,
    ): self {
        return new self(
            identity: new BenchmarkIdentity($benchmarkId, $benchmarkName, $phpVersion),
            execution: new ExecutionMetrics($averageExecutionTime, $minExecutionTime, $maxExecutionTime, $executionCount, $throughput),
            memory: new MemoryMetrics($averageMemoryUsed, $peakMemoryUsed),
            statistics: new StatisticalMetrics($standardDeviation, $coefficientOfVariation, $percentiles),
        );
    }

    /**
     * Create empty statistics for a benchmark with no data.
     *
     * @SuppressWarnings("PHPMD.StaticAccess") - Static factory method pattern is acceptable for value objects
     */
    public static function empty(string $benchmarkId, string $benchmarkName, string $phpVersion): self
    {
        return new self(
            identity: new BenchmarkIdentity($benchmarkId, $benchmarkName, $phpVersion),
            execution: ExecutionMetrics::empty(),
            memory: MemoryMetrics::empty(),
            statistics: StatisticalMetrics::empty(),
        );
    }
}
