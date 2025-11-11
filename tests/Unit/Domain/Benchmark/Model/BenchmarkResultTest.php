<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Tests\Unit\Domain\Benchmark\Model;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;
use PHPUnit\Framework\TestCase;

final class BenchmarkResultTest extends TestCase
{
    public function testConstructorCreatesImmutableValueObject(): void
    {
        $benchmarkResult = new BenchmarkResult(
            executionTimeMs: 12.5,
            memoryUsedBytes: 1024.0,
            memoryPeakBytes: 2048.0,
        );

        self::assertEqualsWithDelta(12.5, $benchmarkResult->executionTimeMs, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(1024.0, $benchmarkResult->memoryUsedBytes, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(2048.0, $benchmarkResult->memoryPeakBytes, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'execution_time_ms' => 15.75,
            'memory_used_bytes' => 2048.5,
            'memory_peak_bytes' => 4096.25,
        ];

        $benchmarkResult = BenchmarkResult::fromArray($data);

        self::assertEqualsWithDelta(15.75, $benchmarkResult->executionTimeMs, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(2048.5, $benchmarkResult->memoryUsedBytes, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(4096.25, $benchmarkResult->memoryPeakBytes, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithIntegerValues(): void
    {
        $data = [
            'execution_time_ms' => 10,
            'memory_used_bytes' => 1024,
            'memory_peak_bytes' => 2048,
        ];

        $benchmarkResult = BenchmarkResult::fromArray($data);

        self::assertEqualsWithDelta(10.0, $benchmarkResult->executionTimeMs, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(1024.0, $benchmarkResult->memoryUsedBytes, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(2048.0, $benchmarkResult->memoryPeakBytes, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithStringNumericValues(): void
    {
        $data = [
            'execution_time_ms' => '12.5',
            'memory_used_bytes' => '1024.0',
            'memory_peak_bytes' => '2048.5',
        ];

        $benchmarkResult = BenchmarkResult::fromArray($data);

        self::assertEqualsWithDelta(12.5, $benchmarkResult->executionTimeMs, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(1024.0, $benchmarkResult->memoryUsedBytes, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(2048.5, $benchmarkResult->memoryPeakBytes, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithMissingKeysDefaultsToZero(): void
    {
        $data = [];

        $benchmarkResult = BenchmarkResult::fromArray($data);

        self::assertEqualsWithDelta(0.0, $benchmarkResult->executionTimeMs, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $benchmarkResult->memoryUsedBytes, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $benchmarkResult->memoryPeakBytes, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithPartialDataDefaultsMissingToZero(): void
    {
        $data = [
            'execution_time_ms' => 25.5,
        ];

        $benchmarkResult = BenchmarkResult::fromArray($data);

        self::assertEqualsWithDelta(25.5, $benchmarkResult->executionTimeMs, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $benchmarkResult->memoryUsedBytes, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $benchmarkResult->memoryPeakBytes, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithInvalidNonNumericValuesDefaultsToZero(): void
    {
        $data = [
            'execution_time_ms' => 'invalid',
            'memory_used_bytes' => 'not-a-number',
            'memory_peak_bytes' => null,
        ];

        $benchmarkResult = BenchmarkResult::fromArray($data);

        self::assertEqualsWithDelta(0.0, $benchmarkResult->executionTimeMs, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $benchmarkResult->memoryUsedBytes, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $benchmarkResult->memoryPeakBytes, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithBooleanValuesDefaultsToZero(): void
    {
        $data = [
            'execution_time_ms' => true,
            'memory_used_bytes' => false,
            'memory_peak_bytes' => true,
        ];

        $benchmarkResult = BenchmarkResult::fromArray($data);

        self::assertEqualsWithDelta(0.0, $benchmarkResult->executionTimeMs, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $benchmarkResult->memoryUsedBytes, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $benchmarkResult->memoryPeakBytes, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithArrayValuesDefaultsToZero(): void
    {
        $data = [
            'execution_time_ms' => [10.5],
            'memory_used_bytes' => ['value' => 1024],
            'memory_peak_bytes' => [],
        ];

        $benchmarkResult = BenchmarkResult::fromArray($data);

        self::assertEqualsWithDelta(0.0, $benchmarkResult->executionTimeMs, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $benchmarkResult->memoryUsedBytes, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $benchmarkResult->memoryPeakBytes, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithNegativeValues(): void
    {
        $data = [
            'execution_time_ms' => -10.5,
            'memory_used_bytes' => -1024.0,
            'memory_peak_bytes' => -2048.0,
        ];

        $benchmarkResult = BenchmarkResult::fromArray($data);

        // Negative values are allowed (edge case)
        self::assertSame(-10.5, $benchmarkResult->executionTimeMs);
        self::assertSame(-1024.0, $benchmarkResult->memoryUsedBytes);
        self::assertSame(-2048.0, $benchmarkResult->memoryPeakBytes);
    }

    public function testFromArrayWithZeroValues(): void
    {
        $data = [
            'execution_time_ms' => 0,
            'memory_used_bytes' => 0.0,
            'memory_peak_bytes' => 0,
        ];

        $benchmarkResult = BenchmarkResult::fromArray($data);

        self::assertEqualsWithDelta(0.0, $benchmarkResult->executionTimeMs, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $benchmarkResult->memoryUsedBytes, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $benchmarkResult->memoryPeakBytes, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithVeryLargeNumbers(): void
    {
        $data = [
            'execution_time_ms' => 999999.99,
            'memory_used_bytes' => 1073741824.0, // 1GB
            'memory_peak_bytes' => 2147483648.0, // 2GB
        ];

        $benchmarkResult = BenchmarkResult::fromArray($data);

        self::assertEqualsWithDelta(999999.99, $benchmarkResult->executionTimeMs, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(1073741824.0, $benchmarkResult->memoryUsedBytes, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(2147483648.0, $benchmarkResult->memoryPeakBytes, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithExtraKeysIgnoresThem(): void
    {
        $data = [
            'execution_time_ms' => 10.0,
            'memory_used_bytes' => 1024.0,
            'memory_peak_bytes' => 2048.0,
            'extra_field' => 'ignored',
            'another_field' => 123,
        ];

        $benchmarkResult = BenchmarkResult::fromArray($data);

        self::assertEqualsWithDelta(10.0, $benchmarkResult->executionTimeMs, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(1024.0, $benchmarkResult->memoryUsedBytes, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(2048.0, $benchmarkResult->memoryPeakBytes, PHP_FLOAT_EPSILON);
    }
}
