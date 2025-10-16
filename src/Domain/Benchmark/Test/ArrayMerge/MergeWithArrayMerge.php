<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\ArrayMerge;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class MergeWithArrayMerge extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $array1 = range(1, 100);
        $array2 = range(101, 200);
        $array3 = range(201, 300);

        for ($i = 0; 10000 > $i; ++$i) {
            $result = array_merge($array1, $array2, $array3);
        }
    }
}
