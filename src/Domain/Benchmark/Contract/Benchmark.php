<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Contract;

use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;

/**
 * Core Domain interface for benchmark implementations.
 *
 * This interface represents the contract that all benchmarks must fulfill.
 * It is intentionally framework-agnostic to keep the Domain pure.
 */
interface Benchmark
{
    public function getMethodBody(PhpVersion $phpVersion): string;
}
