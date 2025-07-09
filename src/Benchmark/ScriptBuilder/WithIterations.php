<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\ScriptBuilder;

interface WithIterations
{
    public function withIterations(int $iterations): BuilderInterface;
}
