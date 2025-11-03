<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Model;

/**
 * Value Object representing percentile metrics.
 */
final readonly class PercentileMetrics
{
    public function __construct(
        public float $p50,
        public float $p80,
        public float $p90,
        public float $p95,
        public float $p99,
    ) {
    }

    /**
     * @param array<string, float> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            p50: $data['p50'] ?? 0.0,
            p80: $data['p80'] ?? 0.0,
            p90: $data['p90'] ?? 0.0,
            p95: $data['p95'] ?? 0.0,
            p99: $data['p99'] ?? 0.0,
        );
    }
}
