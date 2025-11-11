<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\DTO;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkStatistics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\EnhancedBenchmarkStatistics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\PercentileMetrics;

/**
 * Data Transfer Object for benchmark statistics.
 *
 * Used to transfer data from Application layer to Infrastructure (Controller/Twig)
 * Percentiles are accessible via the percentiles property or convenience methods.
 * Supports enhanced statistics with outlier detection.
 */
final readonly class BenchmarkStatisticsData
{
    public function __construct(
        public string $benchmarkId,
        public string $benchmarkName,
        public string $phpVersion,
        public int $count,
        public float $avg,
        public PercentileMetrics $percentiles,
        public float $memoryUsed,
        public float $memoryPeak,
        public float $min,
        public float $max,
        public float $stdDev,
        public float $cv,
        public float $throughput,
        // Enhanced statistics
        public ?int $outlierCount = null,
        public ?float $outlierPercentage = null,
        public ?float $rawCV = null,
        public ?float $stabilityScore = null,
        public ?string $stabilityRating = null,
    ) {
    }

    public static function fromDomain(BenchmarkStatistics $benchmarkStatistics): self
    {
        // Check if this is an enhanced statistics object
        if ($benchmarkStatistics instanceof EnhancedBenchmarkStatistics) {
            return new self(
                benchmarkId: $benchmarkStatistics->benchmarkId,
                benchmarkName: $benchmarkStatistics->benchmarkName,
                phpVersion: $benchmarkStatistics->phpVersion,
                count: $benchmarkStatistics->executionCount,
                avg: $benchmarkStatistics->averageExecutionTime,
                percentiles: $benchmarkStatistics->percentiles,
                memoryUsed: $benchmarkStatistics->averageMemoryUsed,
                memoryPeak: $benchmarkStatistics->peakMemoryUsed,
                min: $benchmarkStatistics->minExecutionTime,
                max: $benchmarkStatistics->maxExecutionTime,
                stdDev: $benchmarkStatistics->standardDeviation,
                cv: $benchmarkStatistics->coefficientOfVariation,
                throughput: $benchmarkStatistics->throughput,
                outlierCount: $benchmarkStatistics->outlierCount,
                outlierPercentage: $benchmarkStatistics->outlierPercentage,
                rawCV: $benchmarkStatistics->rawCV,
                stabilityScore: $benchmarkStatistics->stabilityScore,
                stabilityRating: $benchmarkStatistics->getStabilityRating(),
            );
        }

        // Fallback to basic statistics
        return new self(
            benchmarkId: $benchmarkStatistics->benchmarkId,
            benchmarkName: $benchmarkStatistics->benchmarkName,
            phpVersion: $benchmarkStatistics->phpVersion,
            count: $benchmarkStatistics->executionCount,
            avg: $benchmarkStatistics->averageExecutionTime,
            percentiles: $benchmarkStatistics->percentiles,
            memoryUsed: $benchmarkStatistics->averageMemoryUsed,
            memoryPeak: $benchmarkStatistics->peakMemoryUsed,
            min: $benchmarkStatistics->minExecutionTime,
            max: $benchmarkStatistics->maxExecutionTime,
            stdDev: $benchmarkStatistics->standardDeviation,
            cv: $benchmarkStatistics->coefficientOfVariation,
            throughput: $benchmarkStatistics->throughput,
        );
    }

    public function getP50(): float
    {
        return $this->percentiles->p50;
    }

    public function getP90(): float
    {
        return $this->percentiles->p90;
    }

    public function getP95(): float
    {
        return $this->percentiles->p95;
    }

    public function getP99(): float
    {
        return $this->percentiles->p99;
    }

    public function hasOutliers(): bool
    {
        return null !== $this->outlierCount && 0 < $this->outlierCount;
    }

    public function getOutlierInfo(): string
    {
        if (!$this->hasOutliers()) {
            return 'No outliers';
        }

        return sprintf(
            '%d outliers (%.1f%%) removed',
            $this->outlierCount,
            $this->outlierPercentage ?? 0,
        );
    }

    public function getCVImprovement(): ?float
    {
        if (null === $this->rawCV || 0.0 === $this->rawCV) {
            return null;
        }

        return (($this->rawCV - $this->cv) / $this->rawCV) * 100;
    }

    public function getStabilityScoreColor(): string
    {
        if (null === $this->stabilityScore) {
            return 'secondary';
        }

        return match (true) {
            90 <= $this->stabilityScore => 'success',
            75 <= $this->stabilityScore => 'info',
            60 <= $this->stabilityScore => 'warning',
            default => 'danger',
        };
    }
}
