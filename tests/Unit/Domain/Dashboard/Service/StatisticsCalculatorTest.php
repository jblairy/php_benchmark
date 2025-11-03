<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Tests\Unit\Domain\Dashboard\Service;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkMetrics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Service\StatisticsCalculator;
use PHPUnit\Framework\TestCase;

final class StatisticsCalculatorTest extends TestCase
{
    private StatisticsCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new StatisticsCalculator();
    }

    public function testCalculateWithEmptyMetricsReturnsZeroStatistics(): void
    {
        $metrics = new BenchmarkMetrics(
            benchmarkId: 'test-bench',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionTimes: [],
            memoryUsages: [],
            memoryPeaks: [],
        );

        $statistics = $this->calculator->calculate($metrics);

        self::assertSame('test-bench', $statistics->benchmarkId);
        self::assertSame('Test Benchmark', $statistics->benchmarkName);
        self::assertSame('php84', $statistics->phpVersion);
        self::assertSame(0, $statistics->executionCount);
        self::assertSame(0.0, $statistics->averageExecutionTime);
        self::assertSame(0.0, $statistics->averageMemoryUsed);
        self::assertSame(0.0, $statistics->peakMemoryUsed);
        self::assertSame(0.0, $statistics->percentiles->p50);
        self::assertSame(0.0, $statistics->percentiles->p90);
        self::assertSame(0.0, $statistics->percentiles->p99);
    }

    public function testCalculateWithSingleValueReturnsCorrectStatistics(): void
    {
        $metrics = new BenchmarkMetrics(
            benchmarkId: 'test-bench',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionTimes: [10.5],
            memoryUsages: [1024.0],
            memoryPeaks: [2048.0],
        );

        $statistics = $this->calculator->calculate($metrics);

        self::assertSame(1, $statistics->executionCount);
        self::assertSame(10.5, $statistics->averageExecutionTime);
        self::assertSame(1024.0, $statistics->averageMemoryUsed);
        self::assertSame(2048.0, $statistics->peakMemoryUsed);
        self::assertSame(10.5, $statistics->percentiles->p50);
        self::assertSame(10.5, $statistics->percentiles->p90);
        self::assertSame(10.5, $statistics->percentiles->p99);
    }

    public function testCalculateWithMultipleValuesReturnsCorrectAverage(): void
    {
        $metrics = new BenchmarkMetrics(
            benchmarkId: 'test-bench',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionTimes: [10.0, 20.0, 30.0],
            memoryUsages: [100.0, 200.0, 300.0],
            memoryPeaks: [150.0, 250.0, 350.0],
        );

        $statistics = $this->calculator->calculate($metrics);

        self::assertSame(3, $statistics->executionCount);
        self::assertSame(20.0, $statistics->averageExecutionTime);
        self::assertSame(200.0, $statistics->averageMemoryUsed);
        self::assertSame(350.0, $statistics->peakMemoryUsed);
    }

    public function testCalculatePercentilesWithSortedData(): void
    {
        // Dataset: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 (10 values)
        $metrics = new BenchmarkMetrics(
            benchmarkId: 'test-bench',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionTimes: [1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0],
            memoryUsages: [100.0],
            memoryPeaks: [100.0],
        );

        $statistics = $this->calculator->calculate($metrics);

        // P50 (50th percentile / median) = 5th value
        self::assertSame(5.0, $statistics->percentiles->p50);

        // P80 (80th percentile) = 8th value
        self::assertSame(8.0, $statistics->percentiles->p80);

        // P90 (90th percentile) = 9th value
        self::assertSame(9.0, $statistics->percentiles->p90);

        // P95 (95th percentile) = 10th value
        self::assertSame(10.0, $statistics->percentiles->p95);

        // P99 (99th percentile) = 10th value (last)
        self::assertSame(10.0, $statistics->percentiles->p99);
    }

    public function testCalculatePercentilesWithUnsortedData(): void
    {
        // Unsorted dataset: should be sorted internally
        $metrics = new BenchmarkMetrics(
            benchmarkId: 'test-bench',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionTimes: [50.0, 10.0, 30.0, 20.0, 40.0],
            memoryUsages: [100.0],
            memoryPeaks: [100.0],
        );

        $statistics = $this->calculator->calculate($metrics);

        // After sorting: [10, 20, 30, 40, 50]
        // P50 (median) = 30 (3rd value)
        self::assertSame(30.0, $statistics->percentiles->p50);

        // P90 = 50 (5th value, 90% of 5 = 4.5 -> ceil = 5)
        self::assertSame(50.0, $statistics->percentiles->p90);
    }

    public function testCalculateWithRealWorldBenchmarkData(): void
    {
        // Realistic benchmark execution times in milliseconds
        $metrics = new BenchmarkMetrics(
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

        $statistics = $this->calculator->calculate($metrics);

        self::assertSame(11, $statistics->executionCount);

        // Average should include the outlier
        self::assertGreaterThan(13.0, $statistics->averageExecutionTime);
        self::assertLessThan(20.0, $statistics->averageExecutionTime);

        // P50 should be around 12.9 (not affected by outlier)
        self::assertGreaterThanOrEqual(12.5, $statistics->percentiles->p50);
        self::assertLessThanOrEqual(13.2, $statistics->percentiles->p50);

        // P99 should be the outlier
        self::assertSame(50.0, $statistics->percentiles->p99);
    }

    public function testCalculateDoesNotModifyOriginalData(): void
    {
        $originalTimes = [30.0, 10.0, 20.0];
        $metrics = new BenchmarkMetrics(
            benchmarkId: 'test-bench',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionTimes: $originalTimes,
            memoryUsages: [100.0],
            memoryPeaks: [100.0],
        );

        $this->calculator->calculate($metrics);

        // Original array should remain unchanged (immutability)
        self::assertSame([30.0, 10.0, 20.0], $originalTimes);
    }
}
