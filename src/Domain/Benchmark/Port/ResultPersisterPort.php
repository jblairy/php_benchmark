<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;

/**
 * Interface for persisting benchmark results.
 * Single Responsibility: Store benchmark results.
 */
interface ResultPersisterPort
{
    public function persist(BenchmarkConfiguration $configuration, BenchmarkResult $result): void;
}
