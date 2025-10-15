<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;

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

    public function hasBenchmark(string $name): bool;
}
