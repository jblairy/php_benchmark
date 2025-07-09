<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\Case;

use Jblairy\PhpBenchmark\Benchmark\AbstractBenchmark;
use Jblairy\PhpBenchmark\PhpVersion\Attribute\All;

final class Loop extends AbstractBenchmark
{
    #[All]
    public function executeWithPhp56(): void
    {
        $x = [];

        for ($i = 0; 100000 > $i; ++$i) {
            $x[] = $i * 2;
        }
    }
}
