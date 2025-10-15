<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class Loop extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $x = [];

        for ($i = 0; 100000 > $i; ++$i) {
            $x[] = $i * 2;
        }
    }
}
