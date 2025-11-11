<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Tests\Unit\Application\Dashboard\DTO;

use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkStatisticsData;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkStatistics;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\PercentileMetrics;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BenchmarkStatisticsData::class)]
final class BenchmarkStatisticsDataTest extends TestCase
{
    public function testConstructorCreatesImmutableDTO(): void
    {
        // Arrange
        $percentileMetrics = new PercentileMetrics(
            p50: 1.5,
            p90: 2.5,
            p95: 3.0,
            p99: 4.0,
        );

        // Act
        $benchmarkStatisticsData = new BenchmarkStatisticsData(
            benchmarkId: 'test-benchmark',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            count: 100,
            avg: 1.8,
            percentiles: $percentileMetrics,
            memoryUsed: 1024.5,
            memoryPeak: 2048.0,
            min: 1.0,
            max: 5.0,
            stdDev: 0.5,
            cv: 27.78,
            throughput: 555.56,
        );

        // Assert
        self::assertSame('test-benchmark', $benchmarkStatisticsData->benchmarkId);
        self::assertSame('Test Benchmark', $benchmarkStatisticsData->benchmarkName);
        self::assertSame('php84', $benchmarkStatisticsData->phpVersion);
        self::assertSame(100, $benchmarkStatisticsData->count);
        self::assertEqualsWithDelta(1.8, $benchmarkStatisticsData->avg, PHP_FLOAT_EPSILON);
        self::assertSame($percentileMetrics, $benchmarkStatisticsData->percentiles);
        self::assertEqualsWithDelta(1024.5, $benchmarkStatisticsData->memoryUsed, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(2048.0, $benchmarkStatisticsData->memoryPeak, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(1.0, $benchmarkStatisticsData->min, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(5.0, $benchmarkStatisticsData->max, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.5, $benchmarkStatisticsData->stdDev, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(27.78, $benchmarkStatisticsData->cv, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(555.56, $benchmarkStatisticsData->throughput, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(1.5, $benchmarkStatisticsData->getP50(), PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(2.5, $benchmarkStatisticsData->getP90(), PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(3.0, $benchmarkStatisticsData->getP95(), PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(4.0, $benchmarkStatisticsData->getP99(), PHP_FLOAT_EPSILON);
    }

    public function testPercentilesAreAccessibleViaGetters(): void
    {
        // Arrange
        $percentileMetrics = new PercentileMetrics(
            p50: 1.5,
            p90: 2.5,
            p95: 3.0,
            p99: 4.0,
        );

        $benchmarkStatisticsData = new BenchmarkStatisticsData(
            benchmarkId: 'test',
            benchmarkName: 'Test',
            phpVersion: 'php84',
            count: 100,
            avg: 1.8,
            percentiles: $percentileMetrics,
            memoryUsed: 1024.5,
            memoryPeak: 2048.0,
            min: 1.0,
            max: 5.0,
            stdDev: 0.5,
            cv: 27.78,
            throughput: 555.56,
        );

        // Act & Assert - All percentiles accessible via getter methods
        self::assertEqualsWithDelta(1.5, $benchmarkStatisticsData->getP50(), PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(2.5, $benchmarkStatisticsData->getP90(), PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(3.0, $benchmarkStatisticsData->getP95(), PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(4.0, $benchmarkStatisticsData->getP99(), PHP_FLOAT_EPSILON);
    }

    public function testFromDomainCreatesDTO(): void
    {
        // Arrange
        $percentileMetrics = new PercentileMetrics(
            p50: 1.5,
            p90: 2.5,
            p95: 3.0,
            p99: 4.0,
        );

        $benchmarkStatistics = new BenchmarkStatistics(
            benchmarkId: 'test-benchmark',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionCount: 100,
            averageExecutionTime: 1.8,
            percentiles: $percentileMetrics,
            averageMemoryUsed: 1024.5,
            peakMemoryUsed: 2048.0,
            minExecutionTime: 1.0,
            maxExecutionTime: 5.0,
            standardDeviation: 0.5,
            coefficientOfVariation: 27.78,
            throughput: 555.56,
        );

        // Act
        $benchmarkStatisticsData = BenchmarkStatisticsData::fromDomain($benchmarkStatistics);

        // Assert
        self::assertSame('test-benchmark', $benchmarkStatisticsData->benchmarkId);
        self::assertSame('Test Benchmark', $benchmarkStatisticsData->benchmarkName);
        self::assertSame('php84', $benchmarkStatisticsData->phpVersion);
        self::assertSame(100, $benchmarkStatisticsData->count);
        self::assertEqualsWithDelta(1.8, $benchmarkStatisticsData->avg, PHP_FLOAT_EPSILON);
        self::assertSame($percentileMetrics, $benchmarkStatisticsData->percentiles);
        self::assertEqualsWithDelta(1024.5, $benchmarkStatisticsData->memoryUsed, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(2048.0, $benchmarkStatisticsData->memoryPeak, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.5, $benchmarkStatisticsData->stdDev, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(27.78, $benchmarkStatisticsData->cv, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(555.56, $benchmarkStatisticsData->throughput, PHP_FLOAT_EPSILON);
    }

    public function testFromDomainPreservesPercentiles(): void
    {
        // Arrange
        $percentileMetrics = new PercentileMetrics(
            p50: 10.0,
            p90: 30.0,
            p95: 40.0,
            p99: 50.0,
        );

        $benchmarkStatistics = new BenchmarkStatistics(
            benchmarkId: 'test',
            benchmarkName: 'Test',
            phpVersion: 'php85',
            executionCount: 50,
            averageExecutionTime: 25.0,
            percentiles: $percentileMetrics,
            averageMemoryUsed: 512.0,
            peakMemoryUsed: 1024.0,
            minExecutionTime: 5.0,
            maxExecutionTime: 60.0,
            standardDeviation: 10.5,
            coefficientOfVariation: 42.0,
            throughput: 40.0,
        );

        // Act
        $benchmarkStatisticsData = BenchmarkStatisticsData::fromDomain($benchmarkStatistics);

        // Assert - Verify percentiles are accessible via getter methods
        self::assertEqualsWithDelta(10.0, $benchmarkStatisticsData->getP50(), PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(30.0, $benchmarkStatisticsData->getP90(), PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(40.0, $benchmarkStatisticsData->getP95(), PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(50.0, $benchmarkStatisticsData->getP99(), PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(5.0, $benchmarkStatisticsData->min, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(60.0, $benchmarkStatisticsData->max, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(10.5, $benchmarkStatisticsData->stdDev, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(42.0, $benchmarkStatisticsData->cv, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(40.0, $benchmarkStatisticsData->throughput, PHP_FLOAT_EPSILON);
    }
}
