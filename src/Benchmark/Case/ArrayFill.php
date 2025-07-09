<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\Case;

use Jblairy\PhpBenchmark\Benchmark\AbstractBenchmark;
use Jblairy\PhpBenchmark\PhpVersion\Attribute\All;

final class ArrayFill extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $arr = array_fill(0, 100000, 'test');
    }
}
