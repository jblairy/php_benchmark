<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\PercentileMetrics;

/**
 * Value Object representing statistical analysis metrics.
 */
final readonly class StatisticalMetrics
{
    public function __construct(
        public float $standardDeviation,
        public float $coefficientOfVariation,
        public PercentileMetrics $percentiles,
    ) {
    }

    public static function create(
        float $standardDeviation,
        float $coefficientOfVariation,
        PercentileMetrics $percentiles,
    ): self {
        return new self($standardDeviation, $coefficientOfVariation, $percentiles);
    }

    public static function empty(): self
    {
        return new self(0.0, 0.0, new PercentileMetrics(0.0, 0.0, 0.0, 0.0));
    }
}
