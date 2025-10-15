<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\DTO;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkStatistics;

/**
 * Data Transfer Object for benchmark statistics
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
    ) {}

    public static function fromDomain(BenchmarkStatistics $statistics): self
    {
        return new self(
            benchmarkId: $statistics->benchmarkId,
            benchmarkName: $statistics->benchmarkName,
            phpVersion: $statistics->phpVersion,
            count: $statistics->executionCount,
            avg: $statistics->averageExecutionTime,
            p50: $statistics->percentiles->p50,
            p80: $statistics->percentiles->p80,
            p90: $statistics->percentiles->p90,
            p95: $statistics->percentiles->p95,
            p99: $statistics->percentiles->p99,
            memoryUsed: $statistics->averageMemoryUsed,
            memoryPeak: $statistics->peakMemoryUsed,
        );
    }

    public function toArray(): array
    {
        return [
            'version' => $this->phpVersion,
            'count' => $this->count,
            'avg' => $this->avg,
            'p50' => $this->p50,
            'p80' => $this->p80,
            'p90' => $this->p90,
            'p95' => $this->p95,
            'p99' => $this->p99,
            'memoryUsed' => $this->memoryUsed,
            'memoryPeak' => $this->memoryPeak,
        ];
    }
}
