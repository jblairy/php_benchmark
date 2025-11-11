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
final class BenchmarkStatisticsData
{
    public float $avg {
        get => $this->basicMetrics->avg;
    }

    public float $min {
        get => $this->basicMetrics->min;
    }

    public float $max {
        get => $this->basicMetrics->max;
    }

    public float $stdDev {
        get => $this->basicMetrics->stdDev;
    }

    public float $cv {
        get => $this->basicMetrics->coefficientOfVariation;
    }

    public float $throughput {
        get => $this->basicMetrics->throughput;
    }

    public float $memoryUsed {
        get => $this->memoryMetrics->memoryUsed;
    }

    public float $memoryPeak {
        get => $this->memoryMetrics->memoryPeak;
    }

    public ?int $outlierCount {
        get => $this->outlierMetrics?->outlierCount;
    }

    public ?float $outlierPercentage {
        get => $this->outlierMetrics?->outlierPercentage;
    }

    public ?float $rawCV {
        get => $this->outlierMetrics?->rawCV;
    }

    public ?float $stabilityScore {
        get => $this->outlierMetrics?->stabilityScore;
    }

    public ?string $stabilityRating {
        get => $this->outlierMetrics?->stabilityRating;
    }

    public function __construct(
        public readonly string $benchmarkId,
        public readonly string $benchmarkName,
        public readonly string $phpVersion,
        public readonly int $count,
        public readonly BasicMetrics $basicMetrics,
        public readonly PercentileMetrics $percentiles,
        public readonly MemoryMetrics $memoryMetrics,
        public readonly ?OutlierMetrics $outlierMetrics = null,
    ) {
    }

    public static function fromDomain(BenchmarkStatistics $benchmarkStatistics): self
    {
        $basicMetrics = new BasicMetrics(
            avg: $benchmarkStatistics->averageExecutionTime,
            min: $benchmarkStatistics->minExecutionTime,
            max: $benchmarkStatistics->maxExecutionTime,
            stdDev: $benchmarkStatistics->standardDeviation,
            coefficientOfVariation: $benchmarkStatistics->coefficientOfVariation,
            throughput: $benchmarkStatistics->throughput,
        );

        $memoryMetrics = new MemoryMetrics(
            memoryUsed: $benchmarkStatistics->averageMemoryUsed,
            memoryPeak: $benchmarkStatistics->peakMemoryUsed,
        );

        $outlierMetrics = null;
        if ($benchmarkStatistics instanceof EnhancedBenchmarkStatistics) {
            $outlierMetrics = new OutlierMetrics(
                outlierCount: $benchmarkStatistics->outlierCount,
                outlierPercentage: $benchmarkStatistics->outlierPercentage,
                rawCV: $benchmarkStatistics->rawCV,
                stabilityScore: $benchmarkStatistics->stabilityScore,
                stabilityRating: $benchmarkStatistics->getStabilityRating(),
            );
        }

        return new self(
            benchmarkId: $benchmarkStatistics->benchmarkId,
            benchmarkName: $benchmarkStatistics->benchmarkName,
            phpVersion: $benchmarkStatistics->phpVersion,
            count: $benchmarkStatistics->executionCount,
            basicMetrics: $basicMetrics,
            percentiles: $benchmarkStatistics->percentiles,
            memoryMetrics: $memoryMetrics,
            outlierMetrics: $outlierMetrics,
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
        return $this->outlierMetrics?->hasOutliers() ?? false;
    }

    public function getOutlierInfo(): string
    {
        return $this->outlierMetrics?->getOutlierInfo() ?? 'No outliers';
    }

    public function getCVImprovement(): ?float
    {
        return $this->outlierMetrics?->getCVImprovement($this->cv);
    }

    public function getStabilityScoreColor(): string
    {
        return $this->outlierMetrics?->getStabilityScoreColor() ?? 'secondary';
    }
}
