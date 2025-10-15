<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;

/**
 * Interface for extracting executable code from benchmark classes.
 * Single Responsibility: Extract method body code for execution.
 */
interface CodeExtractorPort
{
    public function extractCode(Benchmark $benchmark, PhpVersion $phpVersion): string;
}
