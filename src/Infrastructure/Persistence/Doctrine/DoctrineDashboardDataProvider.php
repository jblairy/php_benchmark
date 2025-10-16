<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkMetrics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Port\DashboardDataProviderPort;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity\Pulse;

/**
 * Doctrine adapter implementing DashboardDataProviderPort.
 *
 * Follows Dependency Inversion Principle: implements interface from Domain
 */
final readonly class DoctrineDashboardDataProvider implements DashboardDataProviderPort
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function getAllBenchmarkMetrics(): array
    {
        $entityRepository = $this->entityManager->getRepository(Pulse::class);
        $pulses = $entityRepository->findAll();

        return $this->groupPulsesIntoMetrics($pulses);
    }

    public function getAllPhpVersions(): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('DISTINCT p.phpVersion')
            ->from(Pulse::class, 'p')
            ->orderBy('p.phpVersion', 'ASC');

        /** @var array<int, array{phpVersion: \Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion}> $results */
        $results = $queryBuilder->getQuery()->getResult();

        return array_map(
            fn (array $row): string => $row['phpVersion']->value,
            $results,
        );
    }

    /**
     * Group Pulse entities into BenchmarkMetrics.
     *
     * @param Pulse[] $pulses
     *
     * @return BenchmarkMetrics[]
     */
    private function groupPulsesIntoMetrics(array $pulses): array
    {
        $grouped = [];

        foreach ($pulses as $pulse) {
            $key = sprintf(
                '%s_%s_%s',
                $pulse->benchId,
                $pulse->name,
                $pulse->phpVersion->value,
            );

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'benchmarkId' => $pulse->benchId,
                    'benchmarkName' => $pulse->name,
                    'phpVersion' => $pulse->phpVersion->value,
                    'executionTimes' => [],
                    'memoryUsages' => [],
                    'memoryPeaks' => [],
                ];
            }

            $grouped[$key]['executionTimes'][] = $pulse->executionTimeMs;
            $grouped[$key]['memoryUsages'][] = $pulse->memoryUsedBytes;
            $grouped[$key]['memoryPeaks'][] = $pulse->memoryPeakByte;
        }

        return array_map(
            fn (array $data): BenchmarkMetrics => new BenchmarkMetrics(
                benchmarkId: $data['benchmarkId'],
                benchmarkName: $data['benchmarkName'],
                phpVersion: $data['phpVersion'],
                executionTimes: $data['executionTimes'],
                memoryUsages: $data['memoryUsages'],
                memoryPeaks: $data['memoryPeaks'],
            ),
            $grouped,
        );
    }
}
