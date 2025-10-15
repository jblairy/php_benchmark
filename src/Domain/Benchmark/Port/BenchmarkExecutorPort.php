<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;

/**
 * Interface for executing benchmarks.
 * Single Responsibility: Execute a benchmark and return results.
 */
interface BenchmarkExecutorPort
{
    public function execute(BenchmarkConfiguration $configuration): BenchmarkResult;
}
