<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Service;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkMetrics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\EnhancedBenchmarkStatistics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\PercentileMetrics;

/**
 * Enhanced statistics calculator with outlier detection and removal.
 *
 * This calculator improves upon the basic StatisticsCalculator by:
 * - Detecting and removing outliers before calculating statistics
 * - Providing both raw and cleaned statistics
 * - Offering configurable outlier detection methods
 */
final readonly class EnhancedStatisticsCalculator
{
    private const int PERCENTILE_BASE = 100;

    private const int PERCENTILE_P50 = 50;

    private const int PERCENTILE_P90 = 90;

    private const int PERCENTILE_P95 = 95;

    private const int PERCENTILE_P99 = 99;

    public function __construct(
        private OutlierDetector $outlierDetector,
        private bool $removeOutliers = true,
    ) {
    }

    /**
     * Calculate statistics with optional outlier removal.
     */
    public function calculate(BenchmarkMetrics $benchmarkMetrics): EnhancedBenchmarkStatistics
    {
        if ($benchmarkMetrics->isEmpty()) {
            return $this->createEmptyStatistics($benchmarkMetrics);
        }

        // Detect outliers in execution times
        $outlierDetectionResult = $this->outlierDetector->detectAndRemove($benchmarkMetrics->executionTimes);

        // Use cleaned data if outlier removal is enabled
        $dataToAnalyze = $this->removeOutliers ? $outlierDetectionResult->cleanedData : $benchmarkMetrics->executionTimes;

        // Calculate statistics on the appropriate dataset
        $sortedTimes = $dataToAnalyze;
        sort($sortedTimes);

        $percentileMetrics = new PercentileMetrics(
            p50: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P50),
            p90: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P90),
            p95: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P95),
            p99: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P99),
        );

        $average = $this->calculateAverage($dataToAnalyze);
        $standardDeviation = $this->calculateStandardDeviation($dataToAnalyze, $average);
        $coefficientOfVariation = $this->calculateCoefficientOfVariation($standardDeviation, $average);
        $throughput = $this->calculateThroughput($average);

        // Also calculate raw statistics for comparison
        $rawAverage = $this->calculateAverage($benchmarkMetrics->executionTimes);
        $rawStdDev = $this->calculateStandardDeviation($benchmarkMetrics->executionTimes, $rawAverage);
        $rawCV = $this->calculateCoefficientOfVariation($rawStdDev, $rawAverage);

        return new EnhancedBenchmarkStatistics(
            benchmarkId: $benchmarkMetrics->benchmarkId,
            benchmarkName: $benchmarkMetrics->benchmarkName,
            phpVersion: $benchmarkMetrics->phpVersion,
            executionCount: count($dataToAnalyze),
            averageExecutionTime: $average,
            percentileMetrics: $percentileMetrics,
            averageMemoryUsed: $this->calculateAverage($benchmarkMetrics->memoryUsages),
            peakMemoryUsed: $this->calculateMax($benchmarkMetrics->memoryPeaks),
            minExecutionTime: $this->calculateMin($dataToAnalyze),
            maxExecutionTime: $this->calculateMax($dataToAnalyze),
            standardDeviation: $standardDeviation,
            coefficientOfVariation: $coefficientOfVariation,
            throughput: $throughput,
            // Enhanced metrics
            outlierCount: $outlierDetectionResult->outlierCount,
            outlierPercentage: $outlierDetectionResult->getOutlierPercentage(),
            outliers: $outlierDetectionResult->outliers,
            rawExecutionCount: $benchmarkMetrics->getExecutionCount(),
            rawAverage: $rawAverage,
            rawStdDev: $rawStdDev,
            rawCV: $rawCV,
            stabilityScore: $this->calculateStabilityScore($coefficientOfVariation, $outlierDetectionResult),
        );
    }

    /**
     * Calculate a stability score from 0 to 100.
     * Higher score = more stable benchmark.
     */
    private function calculateStabilityScore(float $cv, OutlierDetectionResult $outlierDetectionResult): float
    {
        // Base score from CV% (lower CV = higher score)
        $cvScore = max(0, 100 - ($cv * 5)); // CV of 20% = score of 0

        // Penalty for outliers
        $outlierPenalty = min(30, $outlierDetectionResult->getOutlierPercentage() * 3); // Max 30 point penalty

        // Final score
        $score = max(0, $cvScore - $outlierPenalty);

        return round($score, 2);
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

        return sqrt($sumSquaredDifferences / ($count - 1));
    }

    private function calculateCoefficientOfVariation(float $standardDeviation, float $average): float
    {
        if (0.0 === $average) {
            return 0.0;
        }

        return ($standardDeviation / $average) * 100.0;
    }

    private function calculateThroughput(float $averageTimeMs): float
    {
        if (0.0 === $averageTimeMs) {
            return 0.0;
        }

        return 1000.0 / $averageTimeMs;
    }

    private function createEmptyStatistics(BenchmarkMetrics $benchmarkMetrics): EnhancedBenchmarkStatistics
    {
        return new EnhancedBenchmarkStatistics(
            benchmarkId: $benchmarkMetrics->benchmarkId,
            benchmarkName: $benchmarkMetrics->benchmarkName,
            phpVersion: $benchmarkMetrics->phpVersion,
            executionCount: 0,
            averageExecutionTime: 0.0,
            percentileMetrics: new PercentileMetrics(0.0, 0.0, 0.0, 0.0),
            averageMemoryUsed: 0.0,
            peakMemoryUsed: 0.0,
            minExecutionTime: 0.0,
            maxExecutionTime: 0.0,
            standardDeviation: 0.0,
            coefficientOfVariation: 0.0,
            throughput: 0.0,
            // Enhanced metrics
            outlierCount: 0,
            outlierPercentage: 0.0,
            outliers: [],
            rawExecutionCount: 0,
            rawAverage: 0.0,
            rawStdDev: 0.0,
            rawCV: 0.0,
            stabilityScore: 0.0,
        );
    }
}
