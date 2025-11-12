<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Model;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\BenchmarkIdentity;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\ExecutionMetrics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\MemoryMetrics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\OutlierAnalysis;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\RawStatistics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\StatisticalMetrics;

/**
 * Enhanced benchmark statistics with outlier detection metrics.
 *
 * Refactored to use Parameter Object pattern to reduce constructor complexity.
 * Previously had 21 constructor parameters, now has 6 cohesive parameter objects.
 */
final readonly class EnhancedBenchmarkStatistics extends BenchmarkStatistics
{
    public function __construct(
        BenchmarkIdentity $identity,
        ExecutionMetrics $execution,
        MemoryMetrics $memory,
        StatisticalMetrics $statistics,
        public OutlierAnalysis $outlierAnalysis,
        public RawStatistics $rawStatistics,
    ) {
        parent::__construct($identity, $execution, $memory, $statistics);
    }

    /**
     * Factory method for creating enhanced statistics with individual parameters.
     * Useful for backward compatibility and simpler construction.
     *
     * Note: Named differently from parent's create() to avoid signature conflicts.
     *
     * @param array<int, float> $outliers
     *
     * @SuppressWarnings("PHPMD.ExcessiveParameterList") - Factory method with parameter objects would be less readable
     * @SuppressWarnings("PHPMD.StaticAccess") - Static factory method pattern is acceptable
     */
    public static function createEnhanced(
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
        int $outlierCount,
        float $outlierPercentage,
        array $outliers,
        float $stabilityScore,
        int $rawExecutionCount,
        float $rawAverage,
        float $rawStdDev,
        float $rawCV,
    ): self {
        return new self(
            identity: new BenchmarkIdentity($benchmarkId, $benchmarkName, $phpVersion),
            execution: new ExecutionMetrics($averageExecutionTime, $minExecutionTime, $maxExecutionTime, $executionCount, $throughput),
            memory: new MemoryMetrics($averageMemoryUsed, $peakMemoryUsed),
            statistics: new StatisticalMetrics($standardDeviation, $coefficientOfVariation, $percentiles),
            outlierAnalysis: new OutlierAnalysis($outlierCount, $outlierPercentage, $outliers, $stabilityScore),
            rawStatistics: new RawStatistics($rawExecutionCount, $rawAverage, $rawStdDev, $rawCV),
        );
    }

    /**
     * Create empty enhanced statistics for a benchmark with no data.
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
            outlierAnalysis: OutlierAnalysis::empty(),
            rawStatistics: RawStatistics::empty(),
        );
    }

    /**
     * Get improvement in CV% after outlier removal.
     */
    public function getCVImprovement(): float
    {
        return $this->rawStatistics->calculateCVImprovement($this->statistics->coefficientOfVariation);
    }

    /**
     * Check if the benchmark is considered stable.
     */
    public function isStable(float $maxCV = 10.0): bool
    {
        return $this->statistics->coefficientOfVariation <= $maxCV;
    }

    /**
     * Get a stability rating.
     */
    public function getStabilityRating(): string
    {
        return $this->outlierAnalysis->getStabilityRating();
    }

    /**
     * Get outlier summary.
     */
    public function getOutlierSummary(): string
    {
        if (0 === $this->outlierAnalysis->outlierCount) {
            return 'No outliers detected';
        }

        return sprintf(
            '%d outliers (%.1f%%) removed, CV improved by %.1f%%',
            $this->outlierAnalysis->outlierCount,
            $this->outlierAnalysis->outlierPercentage,
            $this->getCVImprovement(),
        );
    }
}
