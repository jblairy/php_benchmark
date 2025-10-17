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
    public function findMetricsByBenchmark(string $benchmarkId, string $benchmarkName): array
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
                WHERE p.bench_id = :benchmarkId AND p.name = :benchmarkName
                GROUP BY p.bench_id, p.name, p.php_version
                ORDER BY p.php_version
            SQL;

        $connection = $this->getEntityManager()->getConnection();
        $result = $connection->executeQuery($sql, [
            'benchmarkId' => $benchmarkId,
            'benchmarkName' => $benchmarkName,
        ])->fetchAllAssociative();

        return array_map(
            function (array $row): BenchmarkMetrics {
                /** @var array{bench_id: string, name: string, php_version: string, execution_times: string, memory_usages: string, memory_peaks: string} $row */
                /** @var array<float> $executionTimes */
                $executionTimes = json_decode($row['execution_times'], true);
                /** @var array<float> $memoryUsages */
                $memoryUsages = json_decode($row['memory_usages'], true);
                /** @var array<float> $memoryPeaks */
                $memoryPeaks = json_decode($row['memory_peaks'], true);

                return new BenchmarkMetrics(
                    benchmarkId: $row['bench_id'],
                    benchmarkName: $row['name'],
                    phpVersion: $row['php_version'],
                    executionTimes: $executionTimes,
                    memoryUsages: $memoryUsages,
                    memoryPeaks: $memoryPeaks,
                );
            },
            $result,
        );
    }

    /**
     * @return array<int, array{benchId: string, name: string}>
     */
    public function findUniqueBenchmarks(): array
    {
        // @phpstan-ignore-next-line return.type
        return $this->createQueryBuilder('p')
            ->select('DISTINCT p.benchId, p.name')
            ->getQuery()
            ->getResult();
    }
}
