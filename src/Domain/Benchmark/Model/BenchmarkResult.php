<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Model;

final readonly class BenchmarkResult
{
    public function __construct(
        public float $executionTimeMs,
        public float $memoryUsedBytes,
        public float $memoryPeakBytes,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            executionTimeMs: (float) ($data['execution_time_ms'] ?? 0),
            memoryUsedBytes: (float) ($data['memory_used_bytes'] ?? 0),
            memoryPeakBytes: (float) ($data['memory_peak_bytes'] ?? 0),
        );
    }
}
