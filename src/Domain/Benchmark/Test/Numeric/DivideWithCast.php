<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Numeric;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class DivideWithCast extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        for ($i = 1; 100000 > $i; ++$i) {
            $result = (int) (100 / ($i % 50 + 1));
        }
    }
}
