<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Iteration;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class IterateWithGenerator extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        function generateRange()
        {
            for ($i = 1; 10000 >= $i; ++$i) {
                yield $i;
            }
        }

        $sum = 0;
        foreach (generateRange() as $value) {
            $sum += $value;
        }
    }
}
