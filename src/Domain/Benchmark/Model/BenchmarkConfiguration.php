<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Model;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;

final readonly class BenchmarkConfiguration
{
    public function __construct(
        public Benchmark $benchmark,
        public PhpVersion $phpVersion,
        public int $iterations,
    ) {
        if ($this->iterations <= 0) {
            throw new \InvalidArgumentException('Iterations must be greater than 0');
        }
    }

    public function getBenchmarkName(): string
    {
        $parts = explode('\\', $this->benchmark::class);

        return end($parts);
    }
}
