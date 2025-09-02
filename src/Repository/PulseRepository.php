<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Jblairy\PhpBenchmark\Entity\Pulse;

/**
 * @extends ServiceEntityRepository<Pulse>
 */
class PulseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pulse::class);
    }

    /**
     * Trouve tous les benchmarks uniques (combinaisons de benchId et name)
     *
     * @return array<int, array{benchId: string, name: string}>
     */
    public function findUniqueBenchmarks(): array
    {
        return $this->createQueryBuilder('p')
            ->select('DISTINCT p.benchId, p.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule les statistiques pour un benchmark spécifique
     *
     * @param string $benchId
     * @param string $name
     * @return array<string, mixed>
     */
    public function getStatisticsForBenchmark(string $benchId, string $name): array
    {
        $pulses = $this->findBy(['benchId' => $benchId, 'name' => $name]);

        if (empty($pulses)) {
            return [];
        }

        $executionTimes = array_map(fn(Pulse $pulse) => $pulse->executionTimeMs, $pulses);
        sort($executionTimes);
        $count = count($executionTimes);

        return [
            'benchId' => $benchId,
            'name' => $name,
            'count' => $count,
            'avg' => array_sum($executionTimes) / $count,
            'p50' => $this->percentile($executionTimes, 50),
            'p80' => $this->percentile($executionTimes, 80),
            'p90' => $this->percentile($executionTimes, 90),
            'p95' => $this->percentile($executionTimes, 95),
            'p99' => $this->percentile($executionTimes, 99)
        ];
    }

    /**
     * Calcule une valeur de percentile pour un ensemble de données
     */
    private function percentile(array $data, int $percentile): float
    {
        if (empty($data)) {
            return 0;
        }

        $index = ceil($percentile / 100 * count($data)) - 1;
        return $data[$index] ?? end($data);
    }
}
