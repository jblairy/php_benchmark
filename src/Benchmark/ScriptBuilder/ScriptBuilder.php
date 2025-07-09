<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\ScriptBuilder;

final class ScriptBuilder implements WithIterations, BuilderInterface
{
    private int $iterations = 1;

    private function __construct(private string $methodBody)
    {
    }

    public static function fromMethodBody(string $methodBody): WithIterations
    {
        return new self($methodBody);
    }

    public function withIterations(int $iterations): BuilderInterface
    {
        $this->iterations = $iterations;

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
                    "execution_time_ms" => round(((\$end_time - \$start_time) * 1000)/{$this->iterations}, 4),
                    "memory_used_bytes" => (\$mem_after - \$mem_before)/{$this->iterations},
                    "memory_peak_bytes" => (\$mem_peak_after - \$mem_peak_before)/{$this->iterations},
                ]);
            PHP;

        $oneLineBody = mb_trim((string) preg_replace('/[\r\n]+/', ' ', $bodyScript));

        return str_replace("'", "\\'", $oneLineBody);
    }
}
