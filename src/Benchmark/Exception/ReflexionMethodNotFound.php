<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\Exception;

use RuntimeException;

final class ReflexionMethodNotFound extends RuntimeException
{
    public function __construct(string $benchmarkName, string $version)
    {
        parent::__construct("Reflexion method not found for benchmark {$benchmarkName} and php version {$version}");
    }
}
