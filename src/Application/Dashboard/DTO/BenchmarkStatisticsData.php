<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\DTO;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkStatistics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\PercentileMetrics;
use RuntimeException;

/**
 * Data Transfer Object for benchmark statistics.
 *
 * Used to transfer data from Application layer to Infrastructure (Controller/Twig)
 * Percentiles are exposed directly as properties for easier Twig template access.
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
        // Percentile shortcuts for Twig templates
        public float $p50,
        public float $p80,
        public float $p90,
        public float $p95,
        public float $p99,
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
            // Expose percentiles as direct properties for Twig
            p50: $benchmarkStatistics->percentiles->p50,
            p80: $benchmarkStatistics->percentiles->p80,
            p90: $benchmarkStatistics->percentiles->p90,
            p95: $benchmarkStatistics->percentiles->p95,
            p99: $benchmarkStatistics->percentiles->p99,
        );
    }
}
