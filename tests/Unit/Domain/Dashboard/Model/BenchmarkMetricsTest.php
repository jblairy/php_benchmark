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
        $benchmarkMetrics = new BenchmarkMetrics(
            benchmarkId: 'test-id',
            benchmarkName: 'Test Benchmark',
            phpVersion: 'php84',
            executionTimes: [10.0, 20.0, 30.0],
            memoryUsages: [100.0, 200.0, 300.0],
            memoryPeaks: [150.0, 250.0, 350.0],
        );

        self::assertSame('test-id', $benchmarkMetrics->benchmarkId);
        self::assertSame('Test Benchmark', $benchmarkMetrics->benchmarkName);
        self::assertSame('php84', $benchmarkMetrics->phpVersion);
        self::assertSame([10.0, 20.0, 30.0], $benchmarkMetrics->executionTimes);
        self::assertSame([100.0, 200.0, 300.0], $benchmarkMetrics->memoryUsages);
        self::assertSame([150.0, 250.0, 350.0], $benchmarkMetrics->memoryPeaks);
    }

    public function testGetExecutionCountReturnsCorrectCount(): void
    {
        $benchmarkMetrics = new BenchmarkMetrics(
            benchmarkId: 'test-id',
            benchmarkName: 'Test',
            phpVersion: 'php84',
            executionTimes: [10.0, 20.0, 30.0],
            memoryUsages: [100.0],
            memoryPeaks: [100.0],
        );

        self::assertSame(3, $benchmarkMetrics->getExecutionCount());
    }

    public function testGetExecutionCountReturnsZeroForEmptyArray(): void
    {
        $benchmarkMetrics = new BenchmarkMetrics(
            benchmarkId: 'test-id',
            benchmarkName: 'Test',
            phpVersion: 'php84',
            executionTimes: [],
            memoryUsages: [],
            memoryPeaks: [],
        );

        self::assertSame(0, $benchmarkMetrics->getExecutionCount());
    }

    public function testIsEmptyReturnsTrueForNoExecutions(): void
    {
        $benchmarkMetrics = new BenchmarkMetrics(
            benchmarkId: 'test-id',
            benchmarkName: 'Test',
            phpVersion: 'php84',
            executionTimes: [],
            memoryUsages: [],
            memoryPeaks: [],
        );

        self::assertTrue($benchmarkMetrics->isEmpty());
    }

    public function testIsEmptyReturnsFalseForExecutions(): void
    {
        $benchmarkMetrics = new BenchmarkMetrics(
            benchmarkId: 'test-id',
            benchmarkName: 'Test',
            phpVersion: 'php84',
            executionTimes: [10.0],
            memoryUsages: [100.0],
            memoryPeaks: [100.0],
        );

        self::assertFalse($benchmarkMetrics->isEmpty());
    }

    public function testValueObjectIsReadonly(): void
    {
        $benchmarkMetrics = new BenchmarkMetrics(
            benchmarkId: 'test-id',
            benchmarkName: 'Test',
            phpVersion: 'php84',
            executionTimes: [10.0],
            memoryUsages: [100.0],
            memoryPeaks: [100.0],
        );

        $reflectionClass = new ReflectionClass($benchmarkMetrics);
        self::assertTrue($reflectionClass->isReadOnly());
    }
}
