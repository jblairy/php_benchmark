<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\ScriptBuilder;

interface BuilderInterface
{
    public function build(): string;
}
