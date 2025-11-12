<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Service;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkMetrics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\EnhancedBenchmarkStatistics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\PercentileMetrics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\BenchmarkIdentity;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\ExecutionMetrics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\MemoryMetrics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\OutlierAnalysis;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\RawStatistics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject\StatisticalMetrics;

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

        $outlierDetectionResult = $this->detectOutliers($benchmarkMetrics);
        $dataToAnalyze = $this->selectDataForAnalysis($outlierDetectionResult, $benchmarkMetrics);

        $cleanedStatistics = $this->calculateCleanedStatistics($dataToAnalyze);
        $rawStatistics = $this->calculateRawStatistics($benchmarkMetrics);

        return $this->buildEnhancedStatistics(
            $benchmarkMetrics,
            $cleanedStatistics,
            $rawStatistics,
            $outlierDetectionResult,
        );
    }

    private function detectOutliers(BenchmarkMetrics $benchmarkMetrics): OutlierDetectionResult
    {
        return $this->outlierDetector->detectAndRemove($benchmarkMetrics->executionTimes);
    }

    /**
     * @return array<int, float>
     */
    private function selectDataForAnalysis(
        OutlierDetectionResult $outlierResult,
        BenchmarkMetrics $benchmarkMetrics,
    ): array {
        return $this->removeOutliers
            ? $outlierResult->cleanedData
            : $benchmarkMetrics->executionTimes;
    }

    /**
     * @param array<int, float> $dataToAnalyze
     *
     * @return array{percentiles: PercentileMetrics, average: float, stdDev: float, cv: float, throughput: float, min: float, max: float}
     */
    private function calculateCleanedStatistics(array $dataToAnalyze): array
    {
        $sortedTimes = $dataToAnalyze;
        sort($sortedTimes);

        $percentiles = new PercentileMetrics(
            p50: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P50),
            p90: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P90),
            p95: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P95),
            p99: $this->calculatePercentile($sortedTimes, self::PERCENTILE_P99),
        );

        $average = $this->calculateAverage($dataToAnalyze);
        $stdDev = $this->calculateStandardDeviation($dataToAnalyze, $average);
        $coefficientOfVariation = $this->calculateCoefficientOfVariation($stdDev, $average);
        $throughput = $this->calculateThroughput($average);

        return [
            'percentiles' => $percentiles,
            'average' => $average,
            'stdDev' => $stdDev,
            'cv' => $coefficientOfVariation,
            'throughput' => $throughput,
            'min' => $this->calculateMin($dataToAnalyze),
            'max' => $this->calculateMax($dataToAnalyze),
        ];
    }

    /**
     * @return array{average: float, stdDev: float, cv: float}
     */
    private function calculateRawStatistics(BenchmarkMetrics $benchmarkMetrics): array
    {
        $rawAverage = $this->calculateAverage($benchmarkMetrics->executionTimes);
        $rawStdDev = $this->calculateStandardDeviation($benchmarkMetrics->executionTimes, $rawAverage);
        $rawCV = $this->calculateCoefficientOfVariation($rawStdDev, $rawAverage);

        return [
            'average' => $rawAverage,
            'stdDev' => $rawStdDev,
            'cv' => $rawCV,
        ];
    }

    /**
     * @param array{percentiles: PercentileMetrics, average: float, stdDev: float, cv: float, throughput: float, min: float, max: float} $cleanedStats
     * @param array{average: float, stdDev: float, cv: float}                                                                            $rawStats
     */
    private function buildEnhancedStatistics(
        BenchmarkMetrics $benchmarkMetrics,
        array $cleanedStats,
        array $rawStats,
        OutlierDetectionResult $outlierDetectionResult,
    ): EnhancedBenchmarkStatistics {
        // Build parameter objects
        $identity = new BenchmarkIdentity(
            benchmarkId: $benchmarkMetrics->benchmarkId,
            benchmarkName: $benchmarkMetrics->benchmarkName,
            phpVersion: $benchmarkMetrics->phpVersion,
        );

        $execution = new ExecutionMetrics(
            averageExecutionTime: $cleanedStats['average'],
            minExecutionTime: $cleanedStats['min'],
            maxExecutionTime: $cleanedStats['max'],
            executionCount: count($this->selectDataForAnalysis($outlierDetectionResult, $benchmarkMetrics)),
            throughput: $cleanedStats['throughput'],
        );

        $memory = new MemoryMetrics(
            averageMemoryUsed: $this->calculateAverage($benchmarkMetrics->memoryUsages),
            peakMemoryUsed: $this->calculateMax($benchmarkMetrics->memoryPeaks),
        );

        $statistics = new StatisticalMetrics(
            standardDeviation: $cleanedStats['stdDev'],
            coefficientOfVariation: $cleanedStats['cv'],
            percentiles: $cleanedStats['percentiles'],
        );

        $outlierAnalysis = new OutlierAnalysis(
            outlierCount: $outlierDetectionResult->outlierCount,
            outlierPercentage: $outlierDetectionResult->getOutlierPercentage(),
            outliers: $outlierDetectionResult->outliers,
            stabilityScore: $this->calculateStabilityScore($cleanedStats['cv'], $outlierDetectionResult),
        );

        $rawStatistics = new RawStatistics(
            rawExecutionCount: $benchmarkMetrics->getExecutionCount(),
            rawAverage: $rawStats['average'],
            rawStdDev: $rawStats['stdDev'],
            rawCV: $rawStats['cv'],
        );

        return new EnhancedBenchmarkStatistics(
            identity: $identity,
            execution: $execution,
            memory: $memory,
            statistics: $statistics,
            outlierAnalysis: $outlierAnalysis,
            rawStatistics: $rawStatistics,
        );
    }

    /**
     * Calculate a stability score from 0 to 100.
     * Higher score = more stable benchmark.
     */
    private function calculateStabilityScore(float $coefficientOfVariation, OutlierDetectionResult $outlierDetectionResult): float
    {
        $baseScore = $this->calculateBaseScoreFromCV($coefficientOfVariation);
        $penalty = $this->calculateOutlierPenalty($outlierDetectionResult);

        $score = max(0, $baseScore - $penalty);

        return round($score, 2);
    }

    private function calculateBaseScoreFromCV(float $coefficientOfVariation): float
    {
        $cvImpact = $coefficientOfVariation * 5;

        return max(0, 100 - $cvImpact);
    }

    private function calculateOutlierPenalty(OutlierDetectionResult $outlierDetectionResult): float
    {
        $maxPenalty = 30;
        $penaltyFactor = 3;

        return min($maxPenalty, $outlierDetectionResult->getOutlierPercentage() * $penaltyFactor);
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
        return EnhancedBenchmarkStatistics::empty(
            benchmarkId: $benchmarkMetrics->benchmarkId,
            benchmarkName: $benchmarkMetrics->benchmarkName,
            phpVersion: $benchmarkMetrics->phpVersion,
        );
    }
}
