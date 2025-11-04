<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\DTO;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkStatistics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\PercentileMetrics;
use RuntimeException;

/**
 * Data Transfer Object for benchmark statistics.
 *
 * Used to transfer data from Application layer to Infrastructure (Controller)
 *
 * @property float $p50
 * @property float $p80
 * @property float $p90
 * @property float $p95
 * @property float $p99
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
    ) {
    }

    /**
     * Convenience getter for p50 percentile.
     * Allows accessing $stats.p50 instead of $stats.percentiles.p50 in templates.
     */
    public function getP50(): float
    {
        return $this->percentiles->p50;
    }

    /**
     * Convenience getter for p80 percentile.
     */
    public function getP80(): float
    {
        return $this->percentiles->p80;
    }

    /**
     * Convenience getter for p90 percentile.
     */
    public function getP90(): float
    {
        return $this->percentiles->p90;
    }

    /**
     * Convenience getter for p95 percentile.
     */
    public function getP95(): float
    {
        return $this->percentiles->p95;
    }

    /**
     * Convenience getter for p99 percentile.
     */
    public function getP99(): float
    {
        return $this->percentiles->p99;
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
        );
    }
}
