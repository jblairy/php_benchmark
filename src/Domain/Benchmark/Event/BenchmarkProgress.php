<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Event;

/**
 * Event dispatched during benchmark execution to report progress.
 */
final readonly class BenchmarkProgress
{
    public function __construct(
        public string $benchmarkId,
        public string $benchmarkName,
        public string $phpVersion,
        public int $currentIteration,
        public int $totalIterations,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => 'benchmark.progress',
            'benchmarkId' => $this->benchmarkId,
            'benchmarkName' => $this->benchmarkName,
            'phpVersion' => $this->phpVersion,
            'currentIteration' => $this->currentIteration,
            'totalIterations' => $this->totalIterations,
            'progress' => 0 < $this->totalIterations ? (int) (($this->currentIteration / $this->totalIterations) * 100) : 0,
            'timestamp' => time(),
        ];
    }
}
