<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\InMemory;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class InMemoryBenchmarkRepository implements BenchmarkRepositoryPort
{
    private array $benchmarks;

    public function __construct(
        #[AutowireIterator(Benchmark::class)]
        iterable $benchmarks,
    ) {
        $this->benchmarks = iterator_to_array($benchmarks);
    }

    public function getAllBenchmarks(): array
    {
        return $this->benchmarks;
    }

    public function findBenchmarkByName(string $name): ?Benchmark
    {
        foreach ($this->benchmarks as $benchmark) {
            if ($this->matchesBenchmarkName($benchmark, $name)) {
                return $benchmark;
            }
        }

        return null;
    }

    public function hasBenchmark(string $name): bool
    {
        return $this->findBenchmarkByName($name) instanceof Benchmark;
    }

    private function matchesBenchmarkName(Benchmark $benchmark, string $searchName): bool
    {
        $className = $benchmark::class;
        $parts = explode('\\', $className);
        $shortName = end($parts);

        return $shortName === $searchName || str_ends_with($className, $searchName);
    }
}
