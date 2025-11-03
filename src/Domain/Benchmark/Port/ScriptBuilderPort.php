<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

/**
 * Port for building executable PHP scripts from benchmark code.
 *
 * This abstraction allows the Domain to be independent of the specific
 * script building strategy (instrumented, raw, etc.).
 */
interface ScriptBuilderPort
{
    /**
     * Builds an executable PHP script from the given code.
     *
     * @param string $code The benchmark code to wrap in a script
     *
     * @return string The complete executable PHP script
     */
    public function build(string $code): string;
}
