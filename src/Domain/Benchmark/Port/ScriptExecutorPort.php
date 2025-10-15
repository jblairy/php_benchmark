<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\ExecutionContext;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;

/**
 * Interface for executing PHP scripts in different environments.
 * Single Responsibility: Execute PHP code and return raw results.
 * Open/Closed: Can be extended with different implementations (Docker, Native, SSH, etc.)
 */
interface ScriptExecutorPort
{
    public function executeScript(ExecutionContext $context): BenchmarkResult;
}
