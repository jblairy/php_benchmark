<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Execution\ScriptBuilding;

final readonly class InstrumentedScriptBuilder
{
    public function build(string $methodBody): string
    {
        return $this->wrapWithInstrumentation($methodBody);
    }

    private function wrapWithInstrumentation(string $methodBody): string
    {
        return <<<PHP
            \$start_time = microtime(true);
            \$mem_before = memory_get_usage(true);
            \$mem_peak_before = memory_get_peak_usage(true);

            {$methodBody}

            \$mem_after = memory_get_usage(true);
            \$mem_peak_after = memory_get_peak_usage(true);
            \$end_time = microtime(true);

            echo json_encode([
                "execution_time_ms" => round(((\$end_time - \$start_time) * 1000), 4),
                "memory_used_bytes" => (\$mem_after - \$mem_before),
                "memory_peak_bytes" => (\$mem_peak_after - \$mem_peak_before),
            ]);
        PHP;
    }
}
