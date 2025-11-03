<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Service;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkMetrics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkStatistics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\PercentileMetrics;

/**
 * Domain Service for calculating benchmark statistics.
 */
final readonly class StatisticsCalculator
{
    private const int PERCENTILE_BASE = 100;
    private const int PERCENTILE_P50 = 50;
    private const int PERCENTILE_P80 = 80;
    private const int PERCENTILE_P90 = 90;
    private const int PERCENTILE_P95 = 95;
    private const int PERCENTILE_P99 = 99;

    public function calculate(BenchmarkMetrics $benchmarkMetrics): BenchmarkStatistics
    {
        if ($benchmarkMetrics->isEmpty()) {
            return $this->createEmptyStatistics($benchmarkMetrics);
        }

        $sortedTimes = $benchmarkMetrics->executionTimes;
        sort($sortedTimes);

        $percentileMetrics = new PercentileMetrics(
            p50: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P50),
            p80: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P80),
            p90: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P90),
            p95: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P95),
            p99: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P99),
        );

        return new BenchmarkStatistics(
            benchmarkId: $benchmarkMetrics->benchmarkId,
            benchmarkName: $benchmarkMetrics->benchmarkName,
            phpVersion: $benchmarkMetrics->phpVersion,
            executionCount: $benchmarkMetrics->getExecutionCount(),
            averageExecutionTime: $this->calculateAverage($benchmarkMetrics->executionTimes),
            percentiles: $percentileMetrics,
            averageMemoryUsed: $this->calculateAverage($benchmarkMetrics->memoryUsages),
            peakMemoryUsed: $this->calculateMax($benchmarkMetrics->memoryPeaks),
        );
    }

    /**
     * @param array<int, float> $sortedData
     */
    private function calculatePercentile(array $sortedData, int $percentile): float
    {
        $count = count($sortedData);
        if (0 === $count) {
            return 0.0;
        }

        $index = (int) ceil($percentile / self::PERCENTILE_BASE * $count) - 1;

        return $sortedData[$index] ?? end($sortedData);
    }

    /**
     * @param array<int, float> $values
     */
    private function calculateAverage(array $values): float
    {
        $count = count($values);
        if (0 === $count) {
            return 0.0;
        }

        return array_sum($values) / $count;
    }

    /**
     * @param array<int, float> $values
     */
    private function calculateMax(array $values): float
    {
        if ([] === $values) {
            return 0.0;
        }

        $maxValue = max($values);

        return is_float($maxValue) || is_int($maxValue) ? (float) $maxValue : 0.0;
    }

    private function createEmptyStatistics(BenchmarkMetrics $benchmarkMetrics): BenchmarkStatistics
    {
        return new BenchmarkStatistics(
            benchmarkId: $benchmarkMetrics->benchmarkId,
            benchmarkName: $benchmarkMetrics->benchmarkName,
            phpVersion: $benchmarkMetrics->phpVersion,
            executionCount: 0,
            averageExecutionTime: 0.0,
            percentiles: new PercentileMetrics(0.0, 0.0, 0.0, 0.0, 0.0),
            averageMemoryUsed: 0.0,
            peakMemoryUsed: 0.0,
        );
    }
}
