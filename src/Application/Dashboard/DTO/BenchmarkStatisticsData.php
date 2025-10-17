<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\DTO;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkStatistics;

/**
 * Data Transfer Object for benchmark statistics.
 *
 * Used to transfer data from Application layer to Infrastructure (Controller)
 */
final readonly class BenchmarkStatisticsData
{
    public function __construct(
        public string $benchmarkId,
        public string $benchmarkName,
        public string $phpVersion,
        public int $count,
        public float $avg,
        public float $p50,
        public float $p80,
        public float $p90,
        public float $p95,
        public float $p99,
        public float $memoryUsed,
        public float $memoryPeak,
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
            p50: $benchmarkStatistics->percentiles->p50,
            p80: $benchmarkStatistics->percentiles->p80,
            p90: $benchmarkStatistics->percentiles->p90,
            p95: $benchmarkStatistics->percentiles->p95,
            p99: $benchmarkStatistics->percentiles->p99,
            memoryUsed: $benchmarkStatistics->averageMemoryUsed,
            memoryPeak: $benchmarkStatistics->peakMemoryUsed,
        );
    }
}
