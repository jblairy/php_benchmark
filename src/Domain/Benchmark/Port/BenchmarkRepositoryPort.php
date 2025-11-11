<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\DashboardStats;

/**
 * Interface for managing available benchmarks.
 * Single Responsibility: Register and retrieve benchmarks.
 */
interface BenchmarkRepositoryPort
{
    /**
     * @return Benchmark[]
     */
    public function getAllBenchmarks(): array;

    public function findBenchmarkByName(string $name): ?Benchmark;

    public function findBenchmarkBySlug(string $slug): ?Benchmark;

    public function hasBenchmark(string $name): bool;

    /**
     * Get dashboard overview statistics.
     */
    public function getDashboardStats(): DashboardStats;

    /**
     * Get top N most populated categories.
     *
     * @return string[] Array of category names
     */
    public function getTopCategories(int $limit = 3): array;
}
