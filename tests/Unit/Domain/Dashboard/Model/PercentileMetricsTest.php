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
        $metrics = new PercentileMetrics(
            p50: 10.5,
            p90: 18.7,
            p95: 22.1,
            p99: 30.5,
        );

        self::assertSame(10.5, $metrics->p50);
        self::assertSame(18.7, $metrics->p90);
        self::assertSame(22.1, $metrics->p95);
        self::assertSame(30.5, $metrics->p99);
    }

    public function testFromArrayWithValidData(): void
    {
        $data = [
            'p50' => 12.5,
            'p90' => 20.0,
            'p95' => 25.0,
            'p99' => 35.0,
        ];

        $metrics = PercentileMetrics::fromArray($data);

        self::assertSame(12.5, $metrics->p50);
        self::assertSame(20.0, $metrics->p90);
        self::assertSame(25.0, $metrics->p95);
        self::assertSame(35.0, $metrics->p99);
    }

    public function testFromArrayWithMissingKeysDefaultsToZero(): void
    {
        $data = [];

        $metrics = PercentileMetrics::fromArray($data);

        self::assertSame(0.0, $metrics->p50);
        self::assertSame(0.0, $metrics->p90);
        self::assertSame(0.0, $metrics->p95);
        self::assertSame(0.0, $metrics->p99);
    }

    public function testFromArrayWithPartialDataDefaultsMissingToZero(): void
    {
        $data = [
            'p50' => 10.0,
            'p90' => 20.0,
        ];

        $metrics = PercentileMetrics::fromArray($data);

        self::assertSame(10.0, $metrics->p50);
        self::assertSame(20.0, $metrics->p90);
        self::assertSame(0.0, $metrics->p95);
        self::assertSame(0.0, $metrics->p99);
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

        $metrics = PercentileMetrics::fromArray($data);

        self::assertSame(10.0, $metrics->p50);
        self::assertSame(20.0, $metrics->p90);
        self::assertSame(25.0, $metrics->p95);
        self::assertSame(30.0, $metrics->p99);
    }

    public function testValueObjectIsReadonly(): void
    {
        $metrics = new PercentileMetrics(
            p50: 10.0,
            p90: 20.0,
            p95: 25.0,
            p99: 30.0,
        );

        $reflection = new ReflectionClass($metrics);
        self::assertTrue($reflection->isReadOnly());
    }

    public function testPercentilesAreInAscendingOrder(): void
    {
        $metrics = new PercentileMetrics(
            p50: 10.0,
            p90: 20.0,
            p95: 25.0,
            p99: 30.0,
        );

        // Verify semantic meaning: higher percentiles should have higher or equal values
        self::assertLessThanOrEqual($metrics->p90, $metrics->p50);
        self::assertLessThanOrEqual($metrics->p95, $metrics->p90);
        self::assertLessThanOrEqual($metrics->p99, $metrics->p95);
    }
}
