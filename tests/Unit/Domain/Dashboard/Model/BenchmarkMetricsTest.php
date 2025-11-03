<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Tests\Unit\Domain\Dashboard\Model;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkMetrics;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class BenchmarkMetricsTest extends TestCase
{
    public function testConstructorCreatesImmutableValueObject(): void
    {
        $metrics = new BenchmarkMetrics(
            benchmarkId: 'test-id',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionTimes: [10.0, 20.0, 30.0],
            memoryUsages: [100.0, 200.0, 300.0],
            memoryPeaks: [150.0, 250.0, 350.0],
        );

        self::assertSame('test-id', $metrics->benchmarkId);
        self::assertSame('Test Benchmark', $metrics->benchmarkName);
        self::assertSame('php84', $metrics->phpVersion);
        self::assertSame([10.0, 20.0, 30.0], $metrics->executionTimes);
        self::assertSame([100.0, 200.0, 300.0], $metrics->memoryUsages);
        self::assertSame([150.0, 250.0, 350.0], $metrics->memoryPeaks);
    }

    public function testGetExecutionCountReturnsCorrectCount(): void
    {
        $metrics = new BenchmarkMetrics(
            benchmarkId: 'test-id',
            benchmarkName: 'Test',
            phpVersion: 'php84',
            executionTimes: [10.0, 20.0, 30.0],
            memoryUsages: [100.0],
            memoryPeaks: [100.0],
        );

        self::assertSame(3, $metrics->getExecutionCount());
    }

    public function testGetExecutionCountReturnsZeroForEmptyArray(): void
    {
        $metrics = new BenchmarkMetrics(
            benchmarkId: 'test-id',
            benchmarkName: 'Test',
            phpVersion: 'php84',
            executionTimes: [],
            memoryUsages: [],
            memoryPeaks: [],
        );

        self::assertSame(0, $metrics->getExecutionCount());
    }

    public function testIsEmptyReturnsTrueForNoExecutions(): void
    {
        $metrics = new BenchmarkMetrics(
            benchmarkId: 'test-id',
            benchmarkName: 'Test',
            phpVersion: 'php84',
            executionTimes: [],
            memoryUsages: [],
            memoryPeaks: [],
        );

        self::assertTrue($metrics->isEmpty());
    }

    public function testIsEmptyReturnsFalseForExecutions(): void
    {
        $metrics = new BenchmarkMetrics(
            benchmarkId: 'test-id',
            benchmarkName: 'Test',
            phpVersion: 'php84',
            executionTimes: [10.0],
            memoryUsages: [100.0],
            memoryPeaks: [100.0],
        );

        self::assertFalse($metrics->isEmpty());
    }

    public function testValueObjectIsReadonly(): void
    {
        $metrics = new BenchmarkMetrics(
            benchmarkId: 'test-id',
            benchmarkName: 'Test',
            phpVersion: 'php84',
            executionTimes: [10.0],
            memoryUsages: [100.0],
            memoryPeaks: [100.0],
        );

        $reflection = new ReflectionClass($metrics);
        self::assertTrue($reflection->isReadOnly());
    }
}
