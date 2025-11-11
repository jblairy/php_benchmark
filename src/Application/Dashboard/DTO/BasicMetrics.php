<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\DTO;

/**
 * Value Object representing basic benchmark metrics.
 */
final readonly class BasicMetrics
{
    public function __construct(
        public float $avg,
        public float $min,
        public float $max,
        public float $stdDev,
        public float $coefficientOfVariation,
        public float $throughput,
    ) {
    }
}
