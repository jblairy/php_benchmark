<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkMetrics;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity\Pulse;

/**
 * @extends ServiceEntityRepository<Pulse>
 */
class PulseRepository extends ServiceEntityRepository implements PulseRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Pulse::class);
    }

    /**
     * @return BenchmarkMetrics[]
     */
    public function findAllGroupedMetrics(): array
    {
        $sql = <<<'SQL'
            SELECT
                p.bench_id,
                p.name,
                p.php_version,
                JSON_ARRAYAGG(p.execution_time_ms) as execution_times,
                JSON_ARRAYAGG(p.memory_used_bytes) as memory_usages,
                JSON_ARRAYAGG(p.memory_peak_byte) as memory_peaks
            FROM pulse p
            GROUP BY p.bench_id, p.name, p.php_version
            ORDER BY p.bench_id, p.php_version
        SQL;

        $connection = $this->getEntityManager()->getConnection();
        $result = $connection->executeQuery($sql)->fetchAllAssociative();

        return array_map(
            fn (array $row): BenchmarkMetrics => new BenchmarkMetrics(
                benchmarkId: $row['bench_id'],
                benchmarkName: $row['name'],
                phpVersion: $row['php_version'],
                executionTimes: json_decode($row['execution_times'], true),
                memoryUsages: json_decode($row['memory_usages'], true),
                memoryPeaks: json_decode($row['memory_peaks'], true),
            ),
            $result,
        );
    }

    /**
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
     * @return array<string, mixed>
     */
    public function getStatisticsForBenchmark(string $benchId, string $name): array
    {
        $pulses = $this->findBy(['benchId' => $benchId, 'name' => $name]);

        if ([] === $pulses) {
            return [];
        }

        $executionTimes = array_map(fn (Pulse $pulse): float => $pulse->executionTimeMs, $pulses);
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
            'p99' => $this->percentile($executionTimes, 99),
        ];
    }

    private function percentile(array $data, int $percentile): float
    {
        if ([] === $data) {
            return 0;
        }

        $index = ceil($percentile / 100 * count($data)) - 1;

        return $data[$index] ?? end($data);
    }
}
