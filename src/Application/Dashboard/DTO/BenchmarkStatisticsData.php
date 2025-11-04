<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\DTO;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkStatistics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\PercentileMetrics;

/**
 * Data Transfer Object for benchmark statistics.
 *
 * Used to transfer data from Application layer to Infrastructure (Controller/Twig)
 * Percentiles are accessible via the percentiles property or convenience methods.
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
    ) {
    }

    public static function fromDomain(BenchmarkStatistics $benchmarkStatistics): self
    {
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
}
