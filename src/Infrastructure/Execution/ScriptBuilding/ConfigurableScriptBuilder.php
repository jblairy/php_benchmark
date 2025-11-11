<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Execution\ScriptBuilding;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\IterationConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptBuilderPort;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Script builder that uses per-benchmark iteration configuration.
 * Falls back to smart defaults based on code complexity analysis.
 */
final class ConfigurableScriptBuilder implements ScriptBuilderPort
{
    private ?IterationConfiguration $currentConfig = null;

    public function __construct(
        #[Autowire(env: 'BENCHMARK_WARMUP_ITERATIONS')]
        private readonly int $defaultWarmupIterations = 10,
        #[Autowire(env: 'BENCHMARK_INNER_ITERATIONS')]
        private readonly int $defaultInnerIterations = 100,
    ) {
    }

    /**
     * Set the iteration configuration for the next build.
     */
    public function setIterationConfiguration(?IterationConfiguration $config): void
    {
        $this->currentConfig = $config;
    }

    public function build(string $methodBody): string
    {
        // Use current config or create smart defaults
        $config = $this->currentConfig ?? IterationConfiguration::createWithDefaults(
            $this->defaultWarmupIterations,
            $this->defaultInnerIterations,
            $methodBody,
        );

        // Reset current config after use
        $this->currentConfig = null;

        $warmupIterations = $config->warmupIterations;
        $innerIterations = $config->innerIterations;

        return <<<PHP
                // Benchmark configuration: {$config->getDescription()}
                // Warmup phase: Run the code several times to stabilize JIT/opcache
                for (\$warmup = 0; \$warmup < {$warmupIterations}; ++\$warmup) {
                    {$methodBody}
                }

                // Reset memory tracking after warmup
                \$mem_before = memory_get_usage(true);
                \$mem_peak_before = memory_get_peak_usage(true);

                // High precision timing with hrtime (nanoseconds)
                \$start_time = hrtime(true);

                // Run the benchmark multiple times to reduce noise
                for (\$inner = 0; \$inner < {$innerIterations}; ++\$inner) {
                    {$methodBody}
                }

                \$end_time = hrtime(true);

                // Memory measurement after all iterations
                \$mem_after = memory_get_usage(true);
                \$mem_peak_after = memory_get_peak_usage(true);

                // Calculate average time per iteration
                \$elapsed_ns = \$end_time - \$start_time;
                \$total_time_ms = \$elapsed_ns / 1_000_000; // nanoseconds to milliseconds
                \$avg_time_ms = \$total_time_ms / {$innerIterations};

                echo json_encode([
                    "execution_time_ms" => round(\$avg_time_ms, 4),
                    "memory_used_bytes" => (\$mem_after - \$mem_before) / {$innerIterations},
                    "memory_peak_bytes" => (\$mem_peak_after - \$mem_peak_before),
                    "inner_iterations" => {$innerIterations},
                    "warmup_iterations" => {$warmupIterations},
                ]);
            PHP;
    }
}
