<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\DashboardStats;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Adapter\DatabaseBenchmark;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity\Benchmark as BenchmarkEntity;
use RuntimeException;

/**
 * Doctrine implementation of BenchmarkRepositoryPort
 * Loads benchmarks from database and adapts them to Domain interface.
 */
final readonly class DoctrineBenchmarkRepository implements BenchmarkRepositoryPort
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return Benchmark[]
     */
    public function getAllBenchmarks(): array
    {
        $entities = $this->entityManager
            ->getRepository(BenchmarkEntity::class)
            ->findAll();

        return array_map(
            fn (BenchmarkEntity $entity): Benchmark => new DatabaseBenchmark($entity),
            $entities,
        );
    }

    public function findBenchmarkByName(string $name): ?Benchmark
    {
        return $this->findBenchmarkBySlug($name) ?? $this->findBenchmarkByNameLegacy($name);
    }

    public function hasBenchmark(string $name): bool
    {
        return $this->findBenchmarkByName($name) instanceof Benchmark;
    }

    public function getDashboardStats(): DashboardStats
    {
        $totalBenchmarks = $this->countTotalBenchmarks();
        $result = $this->fetchPulseStatistics($totalBenchmarks);

        if (!$result instanceof DashboardStats) {
            throw new RuntimeException('Unexpected result type from Doctrine SELECT NEW query');
        }

        return $result;
    }

    public function getTopCategories(int $limit = 3): array
    {
        $results = $this->entityManager
            ->createQuery('
                SELECT b.category, COUNT(b.id) as benchmark_count
                FROM ' . BenchmarkEntity::class . ' b
                GROUP BY b.category
                ORDER BY benchmark_count DESC
            ')
            ->setMaxResults($limit)
            ->getResult();

        return $this->extractCategoryNamesFromQueryResults($results);
    }

    private function findBenchmarkBySlug(string $slug): ?Benchmark
    {
        $entity = $this->entityManager
            ->getRepository(BenchmarkEntity::class)
            ->findOneBy(['slug' => $slug]);

        return $entity instanceof BenchmarkEntity ? new DatabaseBenchmark($entity) : null;
    }

    private function findBenchmarkByNameLegacy(string $name): ?Benchmark
    {
        $entity = $this->entityManager
            ->getRepository(BenchmarkEntity::class)
            ->findOneBy(['name' => $name]);

        return $entity instanceof BenchmarkEntity ? new DatabaseBenchmark($entity) : null;
    }

    private function countTotalBenchmarks(): int
    {
        return (int) $this->entityManager
            ->createQuery('SELECT COUNT(b.id) FROM ' . BenchmarkEntity::class . ' b')
            ->getSingleScalarResult();
    }

    private function fetchPulseStatistics(int $totalBenchmarks): mixed
    {
        return $this->entityManager
            ->createQuery('
                SELECT NEW ' . DashboardStats::class . '(
                    :totalBenchmarks,
                    COUNT(DISTINCT p.phpVersion),
                    COUNT(DISTINCT p.benchId),
                    COUNT(p.id)
                )
                FROM Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity\Pulse p
            ')
            ->setParameter('totalBenchmarks', $totalBenchmarks)
            ->getSingleResult();
    }

    /**
     * @return string[]
     */
    private function extractCategoryNamesFromQueryResults(mixed $results): array
    {
        if (!is_array($results)) {
            return [];
        }

        $categories = [];
        foreach ($results as $row) {
            if (is_array($row) && isset($row['category']) && is_string($row['category'])) {
                $categories[] = $row['category'];
            }
        }

        return $categories;
    }
}
