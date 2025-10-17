<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Repository;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkMetrics;

interface PulseRepositoryInterface
{
    /**
     * @return array<int, array{benchId: string, name: string}>
     */
    public function findUniqueBenchmarks(): array;

    /**
     * @return array<string, mixed>
     */
    public function getStatisticsForBenchmark(string $benchId, string $name): array;

    /**
     * Get metrics for a specific benchmark grouped by PHP version.
     *
     * @return BenchmarkMetrics[]
     */
    public function findMetricsByBenchmark(string $benchmarkId, string $benchmarkName): array;
}
