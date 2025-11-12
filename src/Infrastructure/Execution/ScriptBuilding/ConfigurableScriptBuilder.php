<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Execution\ScriptBuilding;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\IterationConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\IterationConfigurationFactoryPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptBuilderPort;

/**
 * Script builder that uses per-benchmark iteration configuration.
 * Falls back to smart defaults based on code complexity analysis.
 */
final readonly class ConfigurableScriptBuilder implements ScriptBuilderPort
{
    public function __construct(
        private IterationConfigurationFactoryPort $iterConfigFactory,
    ) {
    }

    public function build(string $methodBody): string
    {
        $config = $this->iterConfigFactory->create(
            benchmarkCode: $methodBody,
        );

        return $this->buildScript($methodBody, $config);
    }

    public function buildWithIterationConfig(string $methodBody, IterationConfiguration $config): string
    {
        return $this->buildScript($methodBody, $config);
    }

    private function buildScript(string $methodBody, IterationConfiguration $config): string
    {
        $warmupIterations = $config->warmupIterations;
        $innerIterations = $config->innerIterations;

        return <<<PHP
                // Benchmark configuration: {$config->getDescription()}
                
                // ============================================================
                // Phase 0: CPU Affinity (if available)
                // ============================================================
                
                if (function_exists('pcntl_setaffinity')) {
                    @pcntl_setaffinity(getmypid(), [0, 1]);
                }
                
                // ============================================================
                // Phase 1: GC Control and Memory Pre-allocation
                // ============================================================
                
                \$gc_was_enabled = gc_enabled();
                
                \$dummy = str_repeat('x', 10 * 1024 * 1024);
                unset(\$dummy);
                
                gc_collect_cycles();
                
                // ============================================================
                // Phase 2: Warmup - Stabilize JIT/opcache and CPU caches
                // ============================================================
                for (\$warmup = 0; \$warmup < {$warmupIterations}; ++\$warmup) {
                    {$methodBody}
                }
                
                usleep(1000);
                
                // ============================================================
                // Phase 3: Measurement Preparation
                // ============================================================
                
                gc_disable();
                
                \$mem_before = memory_get_usage(true);
                \$mem_peak_before = memory_get_peak_usage(true);

                \$start_time = hrtime(true);

                // ============================================================
                // Phase 4: Measurement - GC is disabled here
                // ============================================================
                for (\$inner = 0; \$inner < {$innerIterations}; ++\$inner) {
                    {$methodBody}
                }

                \$end_time = hrtime(true);
                
                // ============================================================
                // Phase 5: Cleanup
                // ============================================================
                
                if (\$gc_was_enabled) {
                    gc_enable();
                }

                \$mem_after = memory_get_usage(true);
                \$mem_peak_after = memory_get_peak_usage(true);

                \$elapsed_ns = \$end_time - \$start_time;
                \$total_time_ms = \$elapsed_ns / 1_000_000;
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
