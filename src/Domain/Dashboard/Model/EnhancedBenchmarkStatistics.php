<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Model;

/**
 * Enhanced benchmark statistics with outlier detection metrics.
 */
final readonly class EnhancedBenchmarkStatistics extends BenchmarkStatistics
{
    /**
     * @param array<int, float> $outliers
     */
    public function __construct(
        string $benchmarkId,
        string $benchmarkName,
        string $phpVersion,
        int $executionCount,
        float $averageExecutionTime,
        PercentileMetrics $percentiles,
        float $averageMemoryUsed,
        float $peakMemoryUsed,
        float $minExecutionTime,
        float $maxExecutionTime,
        float $standardDeviation,
        float $coefficientOfVariation,
        float $throughput,
        // Enhanced metrics
        public int $outlierCount,
        public float $outlierPercentage,
        public array $outliers,
        public int $rawExecutionCount,
        public float $rawAverage,
        public float $rawStdDev,
        public float $rawCV,
        public float $stabilityScore,
    ) {
        parent::__construct(
            benchmarkId: $benchmarkId,
            benchmarkName: $benchmarkName,
            phpVersion: $phpVersion,
            executionCount: $executionCount,
            averageExecutionTime: $averageExecutionTime,
            percentiles: $percentiles,
            averageMemoryUsed: $averageMemoryUsed,
            peakMemoryUsed: $peakMemoryUsed,
            minExecutionTime: $minExecutionTime,
            maxExecutionTime: $maxExecutionTime,
            standardDeviation: $standardDeviation,
            coefficientOfVariation: $coefficientOfVariation,
            throughput: $throughput,
        );
    }

    /**
     * Get improvement in CV% after outlier removal.
     */
    public function getCVImprovement(): float
    {
        if (0.0 === $this->rawCV) {
            return 0.0;
        }

        return (($this->rawCV - $this->coefficientOfVariation) / $this->rawCV) * 100.0;
    }

    /**
     * Check if the benchmark is considered stable.
     */
    public function isStable(float $maxCV = 10.0): bool
    {
        return $this->coefficientOfVariation <= $maxCV;
    }

    /**
     * Get a stability rating.
     */
    public function getStabilityRating(): string
    {
        return match (true) {
            90 <= $this->stabilityScore => 'Excellent',
            75 <= $this->stabilityScore => 'Good',
            60 <= $this->stabilityScore => 'Fair',
            40 <= $this->stabilityScore => 'Poor',
            default => 'Very Poor',
        };
    }

    /**
     * Get outlier summary.
     */
    public function getOutlierSummary(): string
    {
        if (0 === $this->outlierCount) {
            return 'No outliers detected';
        }

        return sprintf(
            '%d outliers (%.1f%%) removed, CV improved by %.1f%%',
            $this->outlierCount,
            $this->outlierPercentage,
            $this->getCVImprovement(),
        );
    }
}
