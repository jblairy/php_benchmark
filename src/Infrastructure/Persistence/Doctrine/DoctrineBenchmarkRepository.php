<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Jblairy\PhpBenchmark\Application\Dashboard\DTO\DashboardStatsData;
use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Adapter\DatabaseBenchmark;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity\Benchmark as BenchmarkEntity;

/**
 * Doctrine implementation of BenchmarkRepositoryPort
 * Loads benchmarks from database and adapts them to Domain interface
 */
final readonly class DoctrineBenchmarkRepository implements BenchmarkRepositoryPort
{
    public function __construct(
        private EntityManagerInterface $entityManager
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
            $entities
        );
    }

    public function findBenchmarkByName(string $name): ?Benchmark
    {
        // Search by slug (exact match)
        $entity = $this->entityManager
            ->getRepository(BenchmarkEntity::class)
            ->findOneBy(['slug' => $name]);

        if ($entity instanceof BenchmarkEntity) {
            return new DatabaseBenchmark($entity);
        }

        // Search by name (partial match for backward compatibility)
        $entity = $this->entityManager
            ->getRepository(BenchmarkEntity::class)
            ->findOneBy(['name' => $name]);

        if ($entity instanceof BenchmarkEntity) {
            return new DatabaseBenchmark($entity);
        }

        return null;
    }

    public function hasBenchmark(string $name): bool
    {
        return $this->findBenchmarkByName($name) instanceof Benchmark;
    }

    public function getDashboardStats(): DashboardStatsData
    {
        // Count total benchmarks
        $totalBenchmarks = (int) $this->entityManager
            ->createQuery('SELECT COUNT(b.id) FROM ' . BenchmarkEntity::class . ' b')
            ->getSingleScalarResult();

        // Get pulse statistics using SELECT NEW for type safety
        $result = $this->entityManager
            ->createQuery('
                SELECT NEW ' . DashboardStatsData::class . '(
                    :totalBenchmarks,
                    COUNT(DISTINCT p.phpVersion),
                    COUNT(DISTINCT p.benchId),
                    COUNT(p.id)
                )
                FROM Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity\Pulse p
            ')
            ->setParameter('totalBenchmarks', $totalBenchmarks)
            ->getSingleResult();

        return $result;
    }
}
