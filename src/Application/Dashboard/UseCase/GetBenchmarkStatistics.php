<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\UseCase;

use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkData;
use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkStatisticsData;
use Jblairy\PhpBenchmark\Domain\Dashboard\Port\PulseRepositoryPort;
use Jblairy\PhpBenchmark\Domain\Dashboard\Service\EnhancedStatisticsCalculator;
use Jblairy\PhpBenchmark\Domain\Dashboard\Service\OutlierDetector;

/**
 * Use Case: Get statistics for a single benchmark across all PHP versions.
 * Uses enhanced statistics calculator with outlier detection.
 */
final readonly class GetBenchmarkStatistics
{
    public function __construct(
        private PulseRepositoryPort $pulseRepository,
        private EnhancedStatisticsCalculator $statisticsCalculator,
    ) {
    }

    public function execute(string $benchmarkId, string $benchmarkName): BenchmarkData
    {
        $metrics = $this->pulseRepository->findMetricsByBenchmark($benchmarkId, $benchmarkName);

        $phpVersionStats = [];
        foreach ($metrics as $metric) {
            $statistics = $this->statisticsCalculator->calculate($metric);
            $phpVersionStats[$metric->phpVersion] = BenchmarkStatisticsData::fromDomain($statistics);
        }

        return new BenchmarkData(
            benchmarkId: $benchmarkId,
            benchmarkName: $benchmarkName,
            phpVersions: $phpVersionStats,
        );
    }
}
