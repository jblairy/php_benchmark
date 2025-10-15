<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Exception;

use RuntimeException;

final class BenchmarkNotFound extends RuntimeException
{
    public function __construct(string $name)
    {
        parent::__construct("Benchmark {$name} not found");
    }
}
