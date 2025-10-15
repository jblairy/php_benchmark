<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Numeric;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class AbsWithAbs extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        for ($i = -50000; $i < 50000; ++$i) {
            $result = abs($i);
        }
    }
}
