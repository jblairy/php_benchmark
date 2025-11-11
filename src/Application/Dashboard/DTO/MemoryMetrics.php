<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\DTO;

/**
 * Value Object representing memory usage metrics.
 */
final readonly class MemoryMetrics
{
    public function __construct(
        public float $memoryUsed,
        public float $memoryPeak,
    ) {
    }
}
