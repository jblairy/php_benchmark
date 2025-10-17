<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\UseCase;

use Jblairy\PhpBenchmark\Application\Dashboard\Builder\BenchmarkGroupBuilder;
use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkGroup;
use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkStatisticsData;
use Jblairy\PhpBenchmark\Application\Dashboard\DTO\DashboardData;
use Jblairy\PhpBenchmark\Domain\Dashboard\Port\DashboardDataProviderPort;
use Jblairy\PhpBenchmark\Domain\Dashboard\Service\StatisticsCalculator;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;

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
        $allPhpVersions = $this->getAllPhpVersionsFromEnum();

        return new DashboardData(
            benchmarks: $benchmarkGroups,
            allPhpVersions: $allPhpVersions,
        );
    }

    /**
     * @return string[]
     */
    private function getAllPhpVersionsFromEnum(): array
    {
        return array_map(
            fn (PhpVersion $version): string => $version->value,
            PhpVersion::cases(),
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

        foreach ($allMetrics as $metric) {
            $benchmarkKey = $metric->benchmarkId . '_' . $metric->benchmarkName;

            if (!isset($grouped[$benchmarkKey])) {
                $grouped[$benchmarkKey] = new BenchmarkGroupBuilder(
                    $metric->benchmarkId,
                    $metric->benchmarkName,
                );
            }

            $statistics = $this->statisticsCalculator->calculate($metric);
            $grouped[$benchmarkKey]->addPhpVersion(
                $metric->phpVersion,
                BenchmarkStatisticsData::fromDomain($statistics),
            );
        }

        return array_map(
            fn (BenchmarkGroupBuilder $builder): BenchmarkGroup => $builder->build(),
            $grouped,
        );
    }
}
