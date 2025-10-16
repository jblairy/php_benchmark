<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Exception;

use RuntimeException;

final class ReflexionMethodNotFound extends RuntimeException
{
    public function __construct(string $benchmarkName, string $version)
    {
        parent::__construct(sprintf('Reflexion method not found for benchmark %s and php version %s', $benchmarkName, $version));
    }
}
