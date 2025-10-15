<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\UseCase;

use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkGroup;
use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkStatisticsData;
use Jblairy\PhpBenchmark\Application\Dashboard\DTO\DashboardData;
use Jblairy\PhpBenchmark\Domain\Dashboard\Port\DashboardRepositoryPort;
use Jblairy\PhpBenchmark\Domain\Dashboard\Service\StatisticsCalculator;

/**
 * Use Case: Get Dashboard Statistics
 */
final readonly class GetDashboardStatistics
{
    public function __construct(
        private DashboardRepositoryPort $repository,
        private StatisticsCalculator $statisticsCalculator,
    ) {}

    public function execute(): DashboardData
    {
        $allMetrics = $this->repository->getAllBenchmarkMetrics();
        $benchmarkGroups = $this->groupStatisticsByBenchmark($allMetrics);
        $allPhpVersions = $this->repository->getAllPhpVersions();

        return new DashboardData(
            benchmarks: $benchmarkGroups,
            allPhpVersions: $allPhpVersions,
        );
    }

    /**
     * @param \Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkMetrics[] $allMetrics
     * @return BenchmarkGroup[]
     */
    private function groupStatisticsByBenchmark(array $allMetrics): array
    {
        $grouped = [];

        foreach ($allMetrics as $metrics) {
            $statistics = $this->statisticsCalculator->calculate($metrics);
            $statisticsData = BenchmarkStatisticsData::fromDomain($statistics);
            $benchmarkKey = $metrics->benchmarkId . '_' . $metrics->benchmarkName;

            if (!isset($grouped[$benchmarkKey])) {
                $grouped[$benchmarkKey] = [
                    'benchmarkId' => $metrics->benchmarkId,
                    'benchmarkName' => $metrics->benchmarkName,
                    'phpVersions' => [],
                ];
            }

            $grouped[$benchmarkKey]['phpVersions'][$metrics->phpVersion] = $statisticsData;
        }

        return array_map(
            fn(array $group) => new BenchmarkGroup(
                benchmarkId: $group['benchmarkId'],
                benchmarkName: $group['benchmarkName'],
                phpVersions: $group['phpVersions'],
            ),
            $grouped
        );
    }
}
