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
        $percentiles = new PercentileMetrics(
            p50: 1.5,
            p90: 2.5,
            p95: 3.0,
            p99: 4.0,
        );

        // Act
        $dto = new BenchmarkStatisticsData(
            benchmarkId: 'test-benchmark',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            count: 100,
            avg: 1.8,
            percentiles: $percentiles,
            memoryUsed: 1024.5,
            memoryPeak: 2048.0,
            min: 1.0,
            max: 5.0,
            stdDev: 0.5,
            cv: 27.78,
            throughput: 555.56,
        );

        // Assert
        self::assertSame('test-benchmark', $dto->benchmarkId);
        self::assertSame('Test Benchmark', $dto->benchmarkName);
        self::assertSame('php84', $dto->phpVersion);
        self::assertSame(100, $dto->count);
        self::assertSame(1.8, $dto->avg);
        self::assertSame($percentiles, $dto->percentiles);
        self::assertSame(1024.5, $dto->memoryUsed);
        self::assertSame(2048.0, $dto->memoryPeak);
        self::assertSame(1.0, $dto->min);
        self::assertSame(5.0, $dto->max);
        self::assertSame(0.5, $dto->stdDev);
        self::assertSame(27.78, $dto->cv);
        self::assertSame(555.56, $dto->throughput);
        self::assertSame(1.5, $dto->getP50());
        self::assertSame(2.5, $dto->getP90());
        self::assertSame(3.0, $dto->getP95());
        self::assertSame(4.0, $dto->getP99());
    }

    public function testPercentilesAreAccessibleViaGetters(): void
    {
        // Arrange
        $percentiles = new PercentileMetrics(
            p50: 1.5,
            p90: 2.5,
            p95: 3.0,
            p99: 4.0,
        );

        $dto = new BenchmarkStatisticsData(
            benchmarkId: 'test',
            benchmarkName: 'Test',
            phpVersion: 'php84',
            count: 100,
            avg: 1.8,
            percentiles: $percentiles,
            memoryUsed: 1024.5,
            memoryPeak: 2048.0,
            min: 1.0,
            max: 5.0,
            stdDev: 0.5,
            cv: 27.78,
            throughput: 555.56,
        );

        // Act & Assert - All percentiles accessible via getter methods
        self::assertSame(1.5, $dto->getP50());
        self::assertSame(2.5, $dto->getP90());
        self::assertSame(3.0, $dto->getP95());
        self::assertSame(4.0, $dto->getP99());
    }

    public function testFromDomainCreatesDTO(): void
    {
        // Arrange
        $percentiles = new PercentileMetrics(
            p50: 1.5,
            p90: 2.5,
            p95: 3.0,
            p99: 4.0,
        );

        $domainStats = new BenchmarkStatistics(
            benchmarkId: 'test-benchmark',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionCount: 100,
            averageExecutionTime: 1.8,
            percentiles: $percentiles,
            averageMemoryUsed: 1024.5,
            peakMemoryUsed: 2048.0,
            minExecutionTime: 1.0,
            maxExecutionTime: 5.0,
            standardDeviation: 0.5,
            coefficientOfVariation: 27.78,
            throughput: 555.56,
        );

        // Act
        $dto = BenchmarkStatisticsData::fromDomain($domainStats);

        // Assert
        self::assertSame('test-benchmark', $dto->benchmarkId);
        self::assertSame('Test Benchmark', $dto->benchmarkName);
        self::assertSame('php84', $dto->phpVersion);
        self::assertSame(100, $dto->count);
        self::assertSame(1.8, $dto->avg);
        self::assertSame($percentiles, $dto->percentiles);
        self::assertSame(1024.5, $dto->memoryUsed);
        self::assertSame(2048.0, $dto->memoryPeak);
        self::assertSame(0.5, $dto->stdDev);
        self::assertSame(27.78, $dto->cv);
        self::assertSame(555.56, $dto->throughput);
    }

    public function testFromDomainPreservesPercentiles(): void
    {
        // Arrange
        $percentiles = new PercentileMetrics(
            p50: 10.0,
            p90: 30.0,
            p95: 40.0,
            p99: 50.0,
        );

        $domainStats = new BenchmarkStatistics(
            benchmarkId: 'test',
            benchmarkName: 'Test',
            phpVersion: 'php85',
            executionCount: 50,
            averageExecutionTime: 25.0,
            percentiles: $percentiles,
            averageMemoryUsed: 512.0,
            peakMemoryUsed: 1024.0,
            minExecutionTime: 5.0,
            maxExecutionTime: 60.0,
            standardDeviation: 10.5,
            coefficientOfVariation: 42.0,
            throughput: 40.0,
        );

        // Act
        $dto = BenchmarkStatisticsData::fromDomain($domainStats);

        // Assert - Verify percentiles are accessible via getter methods
        self::assertSame(10.0, $dto->getP50());
        self::assertSame(30.0, $dto->getP90());
        self::assertSame(40.0, $dto->getP95());
        self::assertSame(50.0, $dto->getP99());
        self::assertSame(5.0, $dto->min);
        self::assertSame(60.0, $dto->max);
        self::assertSame(10.5, $dto->stdDev);
        self::assertSame(42.0, $dto->cv);
        self::assertSame(40.0, $dto->throughput);
    }
}
