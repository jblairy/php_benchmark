<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Tests\Unit\Domain\Dashboard\Service;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkMetrics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Service\StatisticsCalculator;
use PHPUnit\Framework\TestCase;

final class StatisticsCalculatorTest extends TestCase
{
    private StatisticsCalculator $statisticsCalculator;

    protected function setUp(): void
    {
        $this->statisticsCalculator = new StatisticsCalculator();
    }

    public function testCalculateWithEmptyMetricsReturnsZeroStatistics(): void
    {
        $benchmarkMetrics = new BenchmarkMetrics(
            benchmarkId: 'test-bench',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionTimes: [],
            memoryUsages: [],
            memoryPeaks: [],
        );

        $statistics = $this->statisticsCalculator->calculate($benchmarkMetrics);

        self::assertSame('test-bench', $statistics->benchmarkId);
        self::assertSame('Test Benchmark', $statistics->benchmarkName);
        self::assertSame('php84', $statistics->phpVersion);
        self::assertSame(0, $statistics->executionCount);
        self::assertEqualsWithDelta(0.0, $statistics->averageExecutionTime, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $statistics->averageMemoryUsed, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $statistics->peakMemoryUsed, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $statistics->minExecutionTime, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $statistics->maxExecutionTime, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $statistics->standardDeviation, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $statistics->coefficientOfVariation, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $statistics->throughput, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $statistics->percentiles->p50, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $statistics->percentiles->p90, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $statistics->percentiles->p99, PHP_FLOAT_EPSILON);
    }

    public function testCalculateWithSingleValueReturnsCorrectStatistics(): void
    {
        $benchmarkMetrics = new BenchmarkMetrics(
            benchmarkId: 'test-bench',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionTimes: [10.5],
            memoryUsages: [1024.0],
            memoryPeaks: [2048.0],
        );

        $statistics = $this->statisticsCalculator->calculate($benchmarkMetrics);

        self::assertSame(1, $statistics->executionCount);
        self::assertEqualsWithDelta(10.5, $statistics->averageExecutionTime, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(1024.0, $statistics->averageMemoryUsed, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(2048.0, $statistics->peakMemoryUsed, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(10.5, $statistics->minExecutionTime, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(10.5, $statistics->maxExecutionTime, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $statistics->standardDeviation, PHP_FLOAT_EPSILON); // Only 1 value, stdDev = 0
        self::assertEqualsWithDelta(0.0, $statistics->coefficientOfVariation, PHP_FLOAT_EPSILON); // stdDev = 0, so CV = 0
        // Throughput = 1000 / 10.5 ≈ 95.238 ops/sec
        self::assertEqualsWithDelta(95.238, $statistics->throughput, 0.001);
        self::assertEqualsWithDelta(10.5, $statistics->percentiles->p50, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(10.5, $statistics->percentiles->p90, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(10.5, $statistics->percentiles->p99, PHP_FLOAT_EPSILON);
    }

    public function testCalculateWithMultipleValuesReturnsCorrectAverage(): void
    {
        $benchmarkMetrics = new BenchmarkMetrics(
            benchmarkId: 'test-bench',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionTimes: [10.0, 20.0, 30.0],
            memoryUsages: [100.0, 200.0, 300.0],
            memoryPeaks: [150.0, 250.0, 350.0],
        );

        $statistics = $this->statisticsCalculator->calculate($benchmarkMetrics);

        self::assertSame(3, $statistics->executionCount);
        self::assertEqualsWithDelta(20.0, $statistics->averageExecutionTime, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(200.0, $statistics->averageMemoryUsed, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(350.0, $statistics->peakMemoryUsed, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(10.0, $statistics->minExecutionTime, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(30.0, $statistics->maxExecutionTime, PHP_FLOAT_EPSILON);
        // Standard deviation: sqrt(((10-20)^2 + (20-20)^2 + (30-20)^2) / 2) = sqrt(200/2) = 10.0
        self::assertEqualsWithDelta(10.0, $statistics->standardDeviation, PHP_FLOAT_EPSILON);
        // CV = (10.0 / 20.0) * 100 = 50%
        self::assertEqualsWithDelta(50.0, $statistics->coefficientOfVariation, PHP_FLOAT_EPSILON);
        // Throughput = 1000 / 20.0 = 50.0 ops/sec
        self::assertEqualsWithDelta(50.0, $statistics->throughput, PHP_FLOAT_EPSILON);
    }

    public function testCalculatePercentilesWithSortedData(): void
    {
        // Dataset: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 (10 values)
        $benchmarkMetrics = new BenchmarkMetrics(
            benchmarkId: 'test-bench',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionTimes: [1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0],
            memoryUsages: [100.0],
            memoryPeaks: [100.0],
        );

        $statistics = $this->statisticsCalculator->calculate($benchmarkMetrics);

        // P50 (50th percentile / median) = 5th value
        self::assertEqualsWithDelta(5.0, $statistics->percentiles->p50, PHP_FLOAT_EPSILON);

        // P90 (90th percentile) = 9th value
        self::assertEqualsWithDelta(9.0, $statistics->percentiles->p90, PHP_FLOAT_EPSILON);

        // P95 (95th percentile) = 10th value
        self::assertEqualsWithDelta(10.0, $statistics->percentiles->p95, PHP_FLOAT_EPSILON);

        // P99 (99th percentile) = 10th value (last)
        self::assertEqualsWithDelta(10.0, $statistics->percentiles->p99, PHP_FLOAT_EPSILON);

        // Min/Max
        self::assertEqualsWithDelta(1.0, $statistics->minExecutionTime, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(10.0, $statistics->maxExecutionTime, PHP_FLOAT_EPSILON);
    }

    public function testCalculatePercentilesWithUnsortedData(): void
    {
        // Unsorted dataset: should be sorted internally
        $benchmarkMetrics = new BenchmarkMetrics(
            benchmarkId: 'test-bench',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionTimes: [50.0, 10.0, 30.0, 20.0, 40.0],
            memoryUsages: [100.0],
            memoryPeaks: [100.0],
        );

        $statistics = $this->statisticsCalculator->calculate($benchmarkMetrics);

        // After sorting: [10, 20, 30, 40, 50]
        // P50 (median) = 30 (3rd value)
        self::assertEqualsWithDelta(30.0, $statistics->percentiles->p50, PHP_FLOAT_EPSILON);

        // P90 = 50 (5th value, 90% of 5 = 4.5 -> ceil = 5)
        self::assertEqualsWithDelta(50.0, $statistics->percentiles->p90, PHP_FLOAT_EPSILON);
    }

    public function testCalculateWithRealWorldBenchmarkData(): void
    {
        // Realistic benchmark execution times in milliseconds
        $benchmarkMetrics = new BenchmarkMetrics(
            benchmarkId: 'array-loop',
            benchmarkName: 'Array Loop Performance',
            phpVersion: 'php84',
            executionTimes: [
                12.5, 12.8, 13.1, 12.9, 13.0,
                12.7, 12.6, 13.2, 12.8, 12.9,
                50.0, // outlier
            ],
            memoryUsages: [
                1024.0, 1025.0, 1026.0, 1024.5, 1025.5,
                1024.8, 1025.2, 1026.1, 1025.3, 1024.9,
                2048.0, // outlier
            ],
            memoryPeaks: [
                2048.0, 2049.0, 2050.0, 2048.5, 2049.5,
                2048.8, 2049.2, 2050.1, 2049.3, 2048.9,
                4096.0, // outlier
            ],
        );

        $statistics = $this->statisticsCalculator->calculate($benchmarkMetrics);

        self::assertSame(11, $statistics->executionCount);

        // Average should include the outlier
        self::assertGreaterThan(13.0, $statistics->averageExecutionTime);
        self::assertLessThan(20.0, $statistics->averageExecutionTime);

        // P50 should be around 12.9 (not affected by outlier)
        self::assertGreaterThanOrEqual(12.5, $statistics->percentiles->p50);
        self::assertLessThanOrEqual(13.2, $statistics->percentiles->p50);

        // P99 should be the outlier
        self::assertEqualsWithDelta(50.0, $statistics->percentiles->p99, PHP_FLOAT_EPSILON);
    }

    public function testCalculateDoesNotModifyOriginalData(): void
    {
        $originalTimes = [30.0, 10.0, 20.0];
        $benchmarkMetrics = new BenchmarkMetrics(
            benchmarkId: 'test-bench',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionTimes: $originalTimes,
            memoryUsages: [100.0],
            memoryPeaks: [100.0],
        );

        $this->statisticsCalculator->calculate($benchmarkMetrics);

        // Original array should remain unchanged (immutability check)
        self::assertCount(3, $originalTimes);
        self::assertEqualsWithDelta(30.0, $originalTimes[0], PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(10.0, $originalTimes[1], PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(20.0, $originalTimes[2], PHP_FLOAT_EPSILON);
    }
}
