<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\UseCase;

use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkGroup;
use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkStatisticsData;
use Jblairy\PhpBenchmark\Application\Dashboard\DTO\DashboardData;
use Jblairy\PhpBenchmark\Domain\Dashboard\Port\DashboardDataProviderPort;
use Jblairy\PhpBenchmark\Domain\Dashboard\Service\StatisticsCalculator;

/**
 * Use Case: Get Dashboard Statistics.
 */
final readonly class GetDashboardStatistics
{
    public function __construct(
        private DashboardDataProviderPort $dashboardDataProvider,
        private StatisticsCalculator $statisticsCalculator,
    ) {
    }

    public function execute(): DashboardData
    {
        $allMetrics = $this->dashboardDataProvider->getAllBenchmarkMetrics();
        $benchmarkGroups = $this->groupStatisticsByBenchmark($allMetrics);
        $allPhpVersions = $this->dashboardDataProvider->getAllPhpVersions();

        return new DashboardData(
            benchmarks: $benchmarkGroups,
            allPhpVersions: $allPhpVersions,
        );
    }

    /**
     * @param \Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkMetrics[] $allMetrics
     *
     * @return BenchmarkGroup[]
     */
    private function groupStatisticsByBenchmark(array $allMetrics): array
    {
        $grouped = [];

        foreach ($allMetrics as $allMetric) {
            $statistics = $this->statisticsCalculator->calculate($allMetric);
            $statisticsData = BenchmarkStatisticsData::fromDomain($statistics);
            $benchmarkKey = $allMetric->benchmarkId . '_' . $allMetric->benchmarkName;

            if (!isset($grouped[$benchmarkKey])) {
                $grouped[$benchmarkKey] = [
                    'benchmarkId' => $allMetric->benchmarkId,
                    'benchmarkName' => $allMetric->benchmarkName,
                    'phpVersions' => [],
                ];
            }

            $grouped[$benchmarkKey]['phpVersions'][$allMetric->phpVersion] = $statisticsData;
        }

        return array_map(
            fn (array $group): BenchmarkGroup => new BenchmarkGroup(
                benchmarkId: $group['benchmarkId'],
                benchmarkName: $group['benchmarkName'],
                phpVersions: $group['phpVersions'],
            ),
            $grouped,
        );
    }
}
