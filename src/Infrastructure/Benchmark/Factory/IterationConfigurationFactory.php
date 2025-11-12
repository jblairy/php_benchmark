<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Benchmark\Factory;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\IterationConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\IterationConfigurationFactoryPort;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Factory for creating IterationConfiguration instances.
 * Centralizes configuration creation logic and environment variable injection.
 */
final readonly class IterationConfigurationFactory implements IterationConfigurationFactoryPort
{
    public function __construct(
        #[Autowire(env: 'BENCHMARK_WARMUP_ITERATIONS')]
        private int $defaultWarmupIterations,
        #[Autowire(env: 'BENCHMARK_INNER_ITERATIONS')]
        private int $defaultInnerIterations,
    ) {
    }

    /**
     * Create IterationConfiguration with smart defaults.
     *
     * @param int|null    $warmupIterations Explicit warmup iterations
     * @param int|null    $innerIterations  Explicit inner iterations
     * @param string|null $benchmarkCode    Code to analyze for complexity-based defaults
     *
     * @SuppressWarnings("PHPMD.StaticAccess") - Calling static factory method is acceptable
     */
    public function create(
        ?int $warmupIterations = null,
        ?int $innerIterations = null,
        ?string $benchmarkCode = null,
    ): IterationConfiguration {
        return IterationConfiguration::createWithDefaults(
            warmupIterations: $warmupIterations,
            innerIterations: $innerIterations,
            benchmarkCode: $benchmarkCode,
            defaultWarmup: $this->defaultWarmupIterations,
            defaultInner: $this->defaultInnerIterations,
        );
    }

    /**
     * Create IterationConfiguration from explicit values.
     */
    public function createFromExplicitValues(
        int $warmupIterations,
        int $innerIterations,
    ): IterationConfiguration {
        return new IterationConfiguration(
            warmupIterations: $warmupIterations,
            innerIterations: $innerIterations,
        );
    }
}
