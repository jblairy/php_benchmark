<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Service;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkMetrics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkStatistics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\PercentileMetrics;

/**
 * Domain Service for calculating benchmark statistics
 */
final readonly class StatisticsCalculator
{
    public function calculate(BenchmarkMetrics $metrics): BenchmarkStatistics
    {
        if ($metrics->isEmpty()) {
            return $this->createEmptyStatistics($metrics);
        }

        $sortedTimes = $metrics->executionTimes;
        sort($sortedTimes);

        $percentiles = new PercentileMetrics(
            p50: $this->calculatePercentile($sortedTimes, 50),
            p80: $this->calculatePercentile($sortedTimes, 80),
            p90: $this->calculatePercentile($sortedTimes, 90),
            p95: $this->calculatePercentile($sortedTimes, 95),
            p99: $this->calculatePercentile($sortedTimes, 99),
        );

        return new BenchmarkStatistics(
            benchmarkId: $metrics->benchmarkId,
            benchmarkName: $metrics->benchmarkName,
            phpVersion: $metrics->phpVersion,
            executionCount: $metrics->getExecutionCount(),
            averageExecutionTime: $this->calculateAverage($metrics->executionTimes),
            percentiles: $percentiles,
            averageMemoryUsed: $this->calculateAverage($metrics->memoryUsages),
            peakMemoryUsed: $this->calculateMax($metrics->memoryPeaks),
        );
    }

    private function calculatePercentile(array $sortedData, int $percentile): float
    {
        $count = count($sortedData);
        if ($count === 0) {
            return 0.0;
        }

        $index = (int) ceil($percentile / 100 * $count) - 1;
        return $sortedData[$index] ?? end($sortedData);
    }

    private function calculateAverage(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }

        return array_sum($values) / $count;
    }

    private function calculateMax(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        return max($values);
    }

    private function createEmptyStatistics(BenchmarkMetrics $metrics): BenchmarkStatistics
    {
        return new BenchmarkStatistics(
            benchmarkId: $metrics->benchmarkId,
            benchmarkName: $metrics->benchmarkName,
            phpVersion: $metrics->phpVersion,
            executionCount: 0,
            averageExecutionTime: 0.0,
            percentiles: new PercentileMetrics(0.0, 0.0, 0.0, 0.0, 0.0),
            averageMemoryUsed: 0.0,
            peakMemoryUsed: 0.0,
        );
    }
}
