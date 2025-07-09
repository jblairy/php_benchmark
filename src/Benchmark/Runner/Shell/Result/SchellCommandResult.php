<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\Runner\Shell\Result;

class SchellCommandResult
{
    public function __construct(
        protected(set) float $executionTimeMs = 0,
        protected(set) float $memoryUsedBytes = 0,
        protected(set) float $memoryPeakUsageBytes = 0,
    ) {
    }
}
