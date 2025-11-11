<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Execution\ScriptBuilding;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\IterationConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptBuilderPort;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Script builder with stability improvements for accurate benchmarking.
 */
final readonly class ScriptBuilder implements ScriptBuilderPort
{
    public function __construct(
        #[Autowire(env: 'BENCHMARK_WARMUP_ITERATIONS')]
        private int $warmupIterations = 10,
        #[Autowire(env: 'BENCHMARK_INNER_ITERATIONS')]
        private int $innerIterations = 1000,
    ) {
    }

    public function build(string $methodBody): string
    {
        $warmupIterations = $this->warmupIterations;
        $innerIterations = $this->innerIterations;

        return $this->buildScript($methodBody, $warmupIterations, $innerIterations);
    }

    public function buildWithIterationConfig(string $methodBody, IterationConfiguration $config): string
    {
        return $this->buildScript($methodBody, $config->warmupIterations, $config->innerIterations);
    }

    private function buildScript(string $methodBody, int $warmupIterations, int $innerIterations): string
    {
        return <<<PHP
                // ============================================================
                // Phase 0: CPU Affinity (if available)
                // ============================================================
                
                // Pin to specific CPU cores to reduce context switching
                // This reduces cache misses and improves consistency
                if (function_exists('pcntl_setaffinity')) {
                    // Pin to CPU cores 0 and 1 (matches docker cpuset)
                    @pcntl_setaffinity(getmypid(), [0, 1]);
                }
                
                // ============================================================
                // Phase 1: GC Control and Memory Pre-allocation
                // ============================================================
                
                // Save original GC state
                \$gc_was_enabled = gc_enabled();
                
                // Pre-allocate memory to reduce allocation overhead during measurement
                \$dummy = str_repeat('x', 10 * 1024 * 1024); // 10MB
                unset(\$dummy);
                
                // Force GC collection before measurement to start in clean state
                gc_collect_cycles();
                
                // ============================================================
                // Phase 2: Warmup - Stabilize JIT/opcache and CPU caches
                // ============================================================
                for (\$warmup = 0; \$warmup < {$warmupIterations}; ++\$warmup) {
                    {$methodBody}
                }
                
                // Stabilization pause: Let CPU caches settle after warmup
                usleep(1000); // 1ms pause
                
                // ============================================================
                // Phase 3: Measurement Preparation
                // ============================================================
                
                // Disable GC during measurement to prevent timing spikes
                gc_disable();
                
                // Reset memory tracking after warmup
                \$mem_before = memory_get_usage(true);
                \$mem_peak_before = memory_get_peak_usage(true);

                // High precision timing with hrtime (nanoseconds)
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
                
                // Re-enable GC if it was enabled before
                if (\$gc_was_enabled) {
                    gc_enable();
                }

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
