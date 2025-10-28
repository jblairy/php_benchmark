<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Event;

/**
 * Event dispatched when a benchmark execution starts.
 */
final readonly class BenchmarkStarted
{
    public function __construct(
        public string $benchmarkId,
        public string $benchmarkName,
        public string $phpVersion,
        public int $totalIterations,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'benchmark.started',
            'benchmarkId' => $this->benchmarkId,
            'benchmarkName' => $this->benchmarkName,
            'phpVersion' => $this->phpVersion,
            'totalIterations' => $this->totalIterations,
            'timestamp' => time(),
        ];
    }
}
