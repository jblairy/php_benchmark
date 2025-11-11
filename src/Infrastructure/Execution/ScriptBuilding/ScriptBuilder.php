<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Execution\ScriptBuilding;

use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptBuilderPort;

/**
 * Script builder with stability improvements for accurate benchmarking.
 * 
 * Features:
 * - Warmup iterations to stabilize JIT/opcache
 * - Multiple inner iterations to reduce noise
 * - High precision timing with hrtime()
 * - Configurable via environment variables
 */
final readonly class ScriptBuilder implements ScriptBuilderPort
{
    private int $warmupIterations;
    private int $innerIterations;

    public function __construct()
    {
        $this->warmupIterations = (int) ($_ENV['BENCHMARK_WARMUP_ITERATIONS'] ?? 10);
        $this->innerIterations = (int) ($_ENV['BENCHMARK_INNER_ITERATIONS'] ?? 1000);
    }

    public function build(string $methodBody): string
    {
        $warmupIterations = $this->warmupIterations;
        $innerIterations = $this->innerIterations;
        
        return <<<PHP
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