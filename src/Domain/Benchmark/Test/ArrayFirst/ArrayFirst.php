<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\ArrayFirst;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php85;
use function Jblairy\PhpBenchmark\Benchmark\Pulse\array_first;

final class ArrayFirst extends AbstractBenchmark
{
    #[Php85]
    public function executeWithPhp85(): void
    {
        $data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        $first = array_first($data);
    }
}
