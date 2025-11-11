<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Execution\ScriptBuilding;

use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptBuilderPort;

/**
 * Configurable script builder with stability improvements.
 * Allows adjusting warmup and inner iterations via environment variables.
 */
final readonly class ConfigurableStableScriptBuilder implements ScriptBuilderPort
{
    private int $warmupIterations;
    private int $innerIterations;
    private bool $useHrtime;

    public function __construct()
    {
        $this->warmupIterations = (int) ($_ENV['BENCHMARK_WARMUP_ITERATIONS'] ?? 5);
        $this->innerIterations = (int) ($_ENV['BENCHMARK_INNER_ITERATIONS'] ?? 100);
        $this->useHrtime = PHP_VERSION_ID >= 70300; // hrtime available since PHP 7.3
    }

    public function build(string $methodBody): string
    {
        return $this->wrapWithStabilityImprovements($methodBody);
    }

    private function wrapWithStabilityImprovements(string $methodBody): string
    {
        $warmupIterations = $this->warmupIterations;
        $innerIterations = $this->innerIterations;
        $timingStart = $this->useHrtime ? 'hrtime(true)' : 'microtime(true)';
        $timingEnd = $this->useHrtime ? 'hrtime(true)' : 'microtime(true)';
        $timeDivisor = $this->useHrtime ? '1_000_000' : '0.001'; // nanoseconds to ms vs seconds to ms
        $useHrtime = $this->useHrtime ? 'true' : 'false';
        
        return <<<PHP
                // Warmup phase: Run the code a few times to stabilize JIT/opcache
                for (\$warmup = 0; \$warmup < {$warmupIterations}; ++\$warmup) {
                    {$methodBody}
                }
                
                // Reset memory tracking after warmup
                \$mem_before = memory_get_usage(true);
                \$mem_peak_before = memory_get_peak_usage(true);
                
                // Start timing with high precision
                \$start_time = {$timingStart};
                
                // Run the benchmark multiple times to reduce noise
                for (\$inner = 0; \$inner < {$innerIterations}; ++\$inner) {
                    {$methodBody}
                }
                
                \$end_time = {$timingEnd};
                
                // Memory measurement after all iterations
                \$mem_after = memory_get_usage(true);
                \$mem_peak_after = memory_get_peak_usage(true);
                
                // Calculate average time per iteration
                \$elapsed = \$end_time - \$start_time;
                \$total_time_ms = \$elapsed / {$timeDivisor};
                \$avg_time_ms = \$total_time_ms / {$innerIterations};
                
                echo json_encode([
                    "execution_time_ms" => round(\$avg_time_ms, 4),
                    "memory_used_bytes" => (\$mem_after - \$mem_before) / {$innerIterations},
                    "memory_peak_bytes" => (\$mem_peak_after - \$mem_peak_before),
                    "inner_iterations" => {$innerIterations},
                    "warmup_iterations" => {$warmupIterations},
                    "timer_type" => {$useHrtime} ? "hrtime" : "microtime",
                ]);
            PHP;
    }
}