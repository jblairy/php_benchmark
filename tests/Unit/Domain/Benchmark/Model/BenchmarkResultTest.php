<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Tests\Unit\Domain\Benchmark\Model;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;
use PHPUnit\Framework\TestCase;

final class BenchmarkResultTest extends TestCase
{
    public function testConstructorCreatesImmutableValueObject(): void
    {
        $result = new BenchmarkResult(
            executionTimeMs: 12.5,
            memoryUsedBytes: 1024.0,
            memoryPeakBytes: 2048.0,
        );

        self::assertSame(12.5, $result->executionTimeMs);
        self::assertSame(1024.0, $result->memoryUsedBytes);
        self::assertSame(2048.0, $result->memoryPeakBytes);
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'execution_time_ms' => 15.75,
            'memory_used_bytes' => 2048.5,
            'memory_peak_bytes' => 4096.25,
        ];

        $result = BenchmarkResult::fromArray($data);

        self::assertSame(15.75, $result->executionTimeMs);
        self::assertSame(2048.5, $result->memoryUsedBytes);
        self::assertSame(4096.25, $result->memoryPeakBytes);
    }

    public function testFromArrayWithIntegerValues(): void
    {
        $data = [
            'execution_time_ms' => 10,
            'memory_used_bytes' => 1024,
            'memory_peak_bytes' => 2048,
        ];

        $result = BenchmarkResult::fromArray($data);

        self::assertSame(10.0, $result->executionTimeMs);
        self::assertSame(1024.0, $result->memoryUsedBytes);
        self::assertSame(2048.0, $result->memoryPeakBytes);
    }

    public function testFromArrayWithStringNumericValues(): void
    {
        $data = [
            'execution_time_ms' => '12.5',
            'memory_used_bytes' => '1024.0',
            'memory_peak_bytes' => '2048.5',
        ];

        $result = BenchmarkResult::fromArray($data);

        self::assertSame(12.5, $result->executionTimeMs);
        self::assertSame(1024.0, $result->memoryUsedBytes);
        self::assertSame(2048.5, $result->memoryPeakBytes);
    }

    public function testFromArrayWithMissingKeysDefaultsToZero(): void
    {
        $data = [];

        $result = BenchmarkResult::fromArray($data);

        self::assertSame(0.0, $result->executionTimeMs);
        self::assertSame(0.0, $result->memoryUsedBytes);
        self::assertSame(0.0, $result->memoryPeakBytes);
    }

    public function testFromArrayWithPartialDataDefaultsMissingToZero(): void
    {
        $data = [
            'execution_time_ms' => 25.5,
        ];

        $result = BenchmarkResult::fromArray($data);

        self::assertSame(25.5, $result->executionTimeMs);
        self::assertSame(0.0, $result->memoryUsedBytes);
        self::assertSame(0.0, $result->memoryPeakBytes);
    }

    public function testFromArrayWithInvalidNonNumericValuesDefaultsToZero(): void
    {
        $data = [
            'execution_time_ms' => 'invalid',
            'memory_used_bytes' => 'not-a-number',
            'memory_peak_bytes' => null,
        ];

        $result = BenchmarkResult::fromArray($data);

        self::assertSame(0.0, $result->executionTimeMs);
        self::assertSame(0.0, $result->memoryUsedBytes);
        self::assertSame(0.0, $result->memoryPeakBytes);
    }

    public function testFromArrayWithBooleanValuesDefaultsToZero(): void
    {
        $data = [
            'execution_time_ms' => true,
            'memory_used_bytes' => false,
            'memory_peak_bytes' => true,
        ];

        $result = BenchmarkResult::fromArray($data);

        self::assertSame(0.0, $result->executionTimeMs);
        self::assertSame(0.0, $result->memoryUsedBytes);
        self::assertSame(0.0, $result->memoryPeakBytes);
    }

    public function testFromArrayWithArrayValuesDefaultsToZero(): void
    {
        $data = [
            'execution_time_ms' => [10.5],
            'memory_used_bytes' => ['value' => 1024],
            'memory_peak_bytes' => [],
        ];

        $result = BenchmarkResult::fromArray($data);

        self::assertSame(0.0, $result->executionTimeMs);
        self::assertSame(0.0, $result->memoryUsedBytes);
        self::assertSame(0.0, $result->memoryPeakBytes);
    }

    public function testFromArrayWithNegativeValues(): void
    {
        $data = [
            'execution_time_ms' => -10.5,
            'memory_used_bytes' => -1024.0,
            'memory_peak_bytes' => -2048.0,
        ];

        $result = BenchmarkResult::fromArray($data);

        // Negative values are allowed (edge case)
        self::assertSame(-10.5, $result->executionTimeMs);
        self::assertSame(-1024.0, $result->memoryUsedBytes);
        self::assertSame(-2048.0, $result->memoryPeakBytes);
    }

    public function testFromArrayWithZeroValues(): void
    {
        $data = [
            'execution_time_ms' => 0,
            'memory_used_bytes' => 0.0,
            'memory_peak_bytes' => 0,
        ];

        $result = BenchmarkResult::fromArray($data);

        self::assertSame(0.0, $result->executionTimeMs);
        self::assertSame(0.0, $result->memoryUsedBytes);
        self::assertSame(0.0, $result->memoryPeakBytes);
    }

    public function testFromArrayWithVeryLargeNumbers(): void
    {
        $data = [
            'execution_time_ms' => 999999.99,
            'memory_used_bytes' => 1073741824.0, // 1GB
            'memory_peak_bytes' => 2147483648.0, // 2GB
        ];

        $result = BenchmarkResult::fromArray($data);

        self::assertSame(999999.99, $result->executionTimeMs);
        self::assertSame(1073741824.0, $result->memoryUsedBytes);
        self::assertSame(2147483648.0, $result->memoryPeakBytes);
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

        $result = BenchmarkResult::fromArray($data);

        self::assertSame(10.0, $result->executionTimeMs);
        self::assertSame(1024.0, $result->memoryUsedBytes);
        self::assertSame(2048.0, $result->memoryPeakBytes);
    }
}
