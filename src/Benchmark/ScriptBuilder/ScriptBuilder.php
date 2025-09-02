<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\ScriptBuilder;

final class ScriptBuilder implements BuilderInterface
{
    private string $methodBody = '';

    public function withBody(string $methodBody): BuilderInterface
    {
        $this->methodBody = $methodBody;

        return $this;
    }

    public function build(): string
    {
        $bodyScript = <<<PHP
                \$start_time = microtime(true);
                \$mem_before = memory_get_usage(true);
                \$mem_peak_before = memory_get_peak_usage(true);
                {$this->methodBody};
                \$mem_after = memory_get_usage(true);
                \$mem_peak_after = memory_get_peak_usage(true);
                \$end_time = microtime(true);
                echo json_encode([
                    "execution_time_ms" => round(((\$end_time - \$start_time) * 1000), 4),
                    "memory_used_bytes" => (\$mem_after - \$mem_before),
                    "memory_peak_bytes" => (\$mem_peak_after - \$mem_peak_before),
                ]);
            PHP;

        return mb_trim((string) preg_replace('/[\r\n]+/', ' ', $bodyScript));
    }
}
