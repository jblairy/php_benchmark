<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject;

/**
 * Value Object representing memory usage metrics.
 */
final readonly class MemoryMetrics
{
    public function __construct(
        public float $averageMemoryUsed,
        public float $peakMemoryUsed,
    ) {
    }

    public static function create(float $averageMemoryUsed, float $peakMemoryUsed): self
    {
        return new self($averageMemoryUsed, $peakMemoryUsed);
    }

    public static function empty(): self
    {
        return new self(0.0, 0.0);
    }
}
