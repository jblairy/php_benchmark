<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark\Result;

use Jblairy\PhpBenchmark\Benchmark\Runner\Shell\Result\SchellCommandResult;
use Jblairy\PhpBenchmark\PhpVersion\Enum\PhpVersion;

final class BenchmarkResult
{
    public function __construct(
        public readonly SchellCommandResult $shellCommandResult,
        public readonly PhpVersion $phpVersion,
        public readonly string $benchmarkName,
    ) {
    }
}
