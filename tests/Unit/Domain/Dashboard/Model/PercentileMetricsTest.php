<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Tests\Unit\Domain\Dashboard\Model;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\PercentileMetrics;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class PercentileMetricsTest extends TestCase
{
    public function testConstructorCreatesImmutableValueObject(): void
    {
        $percentileMetrics = new PercentileMetrics(
            p50: 10.5,
            p90: 18.7,
            p95: 22.1,
            p99: 30.5,
        );

        self::assertEqualsWithDelta(10.5, $percentileMetrics->p50, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(18.7, $percentileMetrics->p90, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(22.1, $percentileMetrics->p95, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(30.5, $percentileMetrics->p99, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'p50' => 12.5,
            'p90' => 20.0,
            'p95' => 25.0,
            'p99' => 35.0,
        ];

        $percentileMetrics = PercentileMetrics::fromArray($data);

        self::assertEqualsWithDelta(12.5, $percentileMetrics->p50, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(20.0, $percentileMetrics->p90, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(25.0, $percentileMetrics->p95, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(35.0, $percentileMetrics->p99, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithMissingKeysDefaultsToZero(): void
    {
        $data = [];

        $percentileMetrics = PercentileMetrics::fromArray($data);

        self::assertEqualsWithDelta(0.0, $percentileMetrics->p50, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $percentileMetrics->p90, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $percentileMetrics->p95, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $percentileMetrics->p99, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithPartialDataDefaultsMissingToZero(): void
    {
        $data = [
            'p50' => 10.0,
            'p90' => 20.0,
        ];

        $percentileMetrics = PercentileMetrics::fromArray($data);

        self::assertEqualsWithDelta(10.0, $percentileMetrics->p50, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(20.0, $percentileMetrics->p90, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $percentileMetrics->p95, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(0.0, $percentileMetrics->p99, PHP_FLOAT_EPSILON);
    }

    public function testFromArrayWithExtraKeysIgnoresThem(): void
    {
        $data = [
            'p50' => 10.0,
            'p90' => 20.0,
            'p95' => 25.0,
            'p99' => 30.0,
            'p100' => 40.0,
            'extra' => 100.0,
        ];

        $percentileMetrics = PercentileMetrics::fromArray($data);

        self::assertEqualsWithDelta(10.0, $percentileMetrics->p50, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(20.0, $percentileMetrics->p90, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(25.0, $percentileMetrics->p95, PHP_FLOAT_EPSILON);
        self::assertEqualsWithDelta(30.0, $percentileMetrics->p99, PHP_FLOAT_EPSILON);
    }

    public function testValueObjectIsReadonly(): void
    {
        $percentileMetrics = new PercentileMetrics(
            p50: 10.0,
            p90: 20.0,
            p95: 25.0,
            p99: 30.0,
        );

        $reflectionClass = new ReflectionClass($percentileMetrics);
        self::assertTrue($reflectionClass->isReadOnly());
    }

    public function testPercentilesAreInAscendingOrder(): void
    {
        $percentileMetrics = new PercentileMetrics(
            p50: 10.0,
            p90: 20.0,
            p95: 25.0,
            p99: 30.0,
        );

        // Verify semantic meaning: higher percentiles should have higher or equal values
        self::assertLessThanOrEqual($percentileMetrics->p90, $percentileMetrics->p50);
        self::assertLessThanOrEqual($percentileMetrics->p95, $percentileMetrics->p90);
        self::assertLessThanOrEqual($percentileMetrics->p99, $percentileMetrics->p95);
    }
}
