<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\IterationConfiguration;

/**
 * Port for creating IterationConfiguration instances.
 * Abstracts away environment variable access from the Domain layer.
 */
interface IterationConfigurationFactoryPort
{
    /**
     * Create configuration from default environment values.
     *
     * @param int|null    $warmupIterations Explicit warmup iterations
     * @param int|null    $innerIterations  Explicit inner iterations
     * @param string|null $benchmarkCode    Code to analyze for complexity-based defaults
     */
    public function create(
        ?int $warmupIterations = null,
        ?int $innerIterations = null,
        ?string $benchmarkCode = null,
    ): IterationConfiguration;

    /**
     * Create configuration with explicit values (overrides defaults).
     */
    public function createFromExplicitValues(
        int $warmupIterations,
        int $innerIterations,
    ): IterationConfiguration;
}
