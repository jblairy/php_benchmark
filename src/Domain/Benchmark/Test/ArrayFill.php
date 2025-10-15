<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class ArrayFill extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $arr = array_fill(0, 100000, 'test');
    }
}
