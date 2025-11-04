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
            p90: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P90),
            p95: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P95),
            p99: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P99),
        );

        $average = $this->calculateAverage($benchmarkMetrics->executionTimes);
        $standardDeviation = $this->calculateStandardDeviation($benchmarkMetrics->executionTimes, $average);
        $coefficientOfVariation = $this->calculateCoefficientOfVariation($standardDeviation, $average);
        $throughput = $this->calculateThroughput($average);

        return new BenchmarkStatistics(
            benchmarkId: $benchmarkMetrics->benchmarkId,
            benchmarkName: $benchmarkMetrics->benchmarkName,
            phpVersion: $benchmarkMetrics->phpVersion,
            executionCount: $benchmarkMetrics->getExecutionCount(),
            averageExecutionTime: $average,
            percentiles: $percentileMetrics,
            averageMemoryUsed: $this->calculateAverage($benchmarkMetrics->memoryUsages),
            peakMemoryUsed: $this->calculateMax($benchmarkMetrics->memoryPeaks),
            minExecutionTime: $this->calculateMin($benchmarkMetrics->executionTimes),
            maxExecutionTime: $this->calculateMax($benchmarkMetrics->executionTimes),
            standardDeviation: $standardDeviation,
            coefficientOfVariation: $coefficientOfVariation,
            throughput: $throughput,
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

    /**
     * @param array<int, float> $values
     */
    private function calculateMin(array $values): float
    {
        if ([] === $values) {
            return 0.0;
        }

        $minValue = min($values);

        return is_float($minValue) || is_int($minValue) ? (float) $minValue : 0.0;
    }

    /**
     * Calculate standard deviation (sample standard deviation).
     *
     * @param array<int, float> $values
     */
    private function calculateStandardDeviation(array $values, float $average): float
    {
        $count = count($values);
        if (1 >= $count) {
            return 0.0;
        }

        $sumSquaredDifferences = 0.0;
        foreach ($values as $value) {
            $difference = $value - $average;
            $sumSquaredDifferences += $difference * $difference;
        }

        // Sample standard deviation (n-1 for Bessel's correction)
        return sqrt($sumSquaredDifferences / ($count - 1));
    }

    /**
     * Calculate coefficient of variation (CV) as percentage.
     * CV = (standard deviation / mean) * 100.
     */
    private function calculateCoefficientOfVariation(float $standardDeviation, float $average): float
    {
        if (0.0 === $average) {
            return 0.0;
        }

        return ($standardDeviation / $average) * 100.0;
    }

    /**
     * Calculate throughput (operations per second).
     * Throughput = 1000 / average_time_ms.
     */
    private function calculateThroughput(float $averageTimeMs): float
    {
        if (0.0 === $averageTimeMs) {
            return 0.0;
        }

        return 1000.0 / $averageTimeMs;
    }

    private function createEmptyStatistics(BenchmarkMetrics $benchmarkMetrics): BenchmarkStatistics
    {
        return new BenchmarkStatistics(
            benchmarkId: $benchmarkMetrics->benchmarkId,
            benchmarkName: $benchmarkMetrics->benchmarkName,
            phpVersion: $benchmarkMetrics->phpVersion,
            executionCount: 0,
            averageExecutionTime: 0.0,
            percentiles: new PercentileMetrics(0.0, 0.0, 0.0, 0.0),
            averageMemoryUsed: 0.0,
            peakMemoryUsed: 0.0,
            minExecutionTime: 0.0,
            maxExecutionTime: 0.0,
            standardDeviation: 0.0,
            coefficientOfVariation: 0.0,
            throughput: 0.0,
        );
    }
}
