<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject;

/**
 * Value Object representing execution time metrics.
 */
final readonly class ExecutionMetrics
{
    public function __construct(
        public float $averageExecutionTime,
        public float $minExecutionTime,
        public float $maxExecutionTime,
        public int $executionCount,
        public float $throughput,
    ) {
    }

    public static function create(
        float $averageExecutionTime,
        float $minExecutionTime,
        float $maxExecutionTime,
        int $executionCount,
        float $throughput,
    ): self {
        return new self($averageExecutionTime, $minExecutionTime, $maxExecutionTime, $executionCount, $throughput);
    }

    public static function empty(): self
    {
        return new self(0.0, 0.0, 0.0, 0, 0.0);
    }
}
