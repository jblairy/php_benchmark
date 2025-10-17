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
     * Get all benchmark metrics grouped by benchmark and PHP version using SQL aggregation.
     *
     * Uses JSON_ARRAYAGG to efficiently aggregate metrics at database level.
     *
     * @return BenchmarkMetrics[]
     */
    public function findAllGroupedMetrics(): array;
}
