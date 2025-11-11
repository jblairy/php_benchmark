<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Execution\ScriptBuilding;

use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptBuilderPort;

/**
 * Script builder that adds stability improvements:
 * - Warmup phase to stabilize JIT/opcache
 * - Multiple inner iterations to reduce noise
 * - Better timing precision
 */
final readonly class StableScriptBuilder implements ScriptBuilderPort
{
    private const WARMUP_ITERATIONS = 5;
    private const INNER_ITERATIONS = 100;

    public function build(string $methodBody): string
    {
        return $this->wrapWithStabilityImprovements($methodBody);
    }

    private function wrapWithStabilityImprovements(string $methodBody): string
    {
        $warmupIterations = self::WARMUP_ITERATIONS;
        $innerIterations = self::INNER_ITERATIONS;
        
        return <<<PHP
                // Warmup phase: Run the code a few times to stabilize JIT/opcache
                for (\$warmup = 0; \$warmup < {$warmupIterations}; ++\$warmup) {
                    {$methodBody}
                }
                
                // Reset memory tracking after warmup
                \$mem_before = memory_get_usage(true);
                \$mem_peak_before = memory_get_peak_usage(true);
                
                // Start timing with high precision
                \$start_time = microtime(true);
                
                // Run the benchmark multiple times to reduce noise
                for (\$inner = 0; \$inner < {$innerIterations}; ++\$inner) {
                    {$methodBody}
                }
                
                \$end_time = microtime(true);
                
                // Memory measurement after all iterations
                \$mem_after = memory_get_usage(true);
                \$mem_peak_after = memory_get_peak_usage(true);
                
                // Calculate average time per iteration
                \$total_time_ms = (\$end_time - \$start_time) * 1000;
                \$avg_time_ms = \$total_time_ms / {$innerIterations};
                
                echo json_encode([
                    "execution_time_ms" => round(\$avg_time_ms, 4),
                    "memory_used_bytes" => (\$mem_after - \$mem_before) / {$innerIterations},
                    "memory_peak_bytes" => (\$mem_peak_after - \$mem_peak_before),
                    "inner_iterations" => {$innerIterations},
                ]);
            PHP;
    }
}