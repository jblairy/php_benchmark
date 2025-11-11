<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\IterationConfiguration;

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

    /**
     * Builds an executable PHP script with custom iteration configuration.
     *
     * @param string                 $code   The benchmark code to wrap in a script
     * @param IterationConfiguration $config The iteration configuration to use
     *
     * @return string The complete executable PHP script
     */
    public function buildWithIterationConfig(string $code, IterationConfiguration $config): string;
}
