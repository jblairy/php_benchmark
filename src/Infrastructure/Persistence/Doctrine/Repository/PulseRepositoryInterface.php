<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Repository;

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
}
