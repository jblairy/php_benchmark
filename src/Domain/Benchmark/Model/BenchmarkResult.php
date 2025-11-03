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
        $executionTime = $data['execution_time_ms'] ?? 0;
        $memoryUsed = $data['memory_used_bytes'] ?? 0;
        $memoryPeak = $data['memory_peak_bytes'] ?? 0;
        
        return new self(
            executionTimeMs: is_numeric($executionTime) ? (float) $executionTime : 0.0,
            memoryUsedBytes: is_numeric($memoryUsed) ? (float) $memoryUsed : 0.0,
            memoryPeakBytes: is_numeric($memoryPeak) ? (float) $memoryPeak : 0.0,
        );
    }
}
