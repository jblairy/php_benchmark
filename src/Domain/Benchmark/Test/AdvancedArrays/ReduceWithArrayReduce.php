<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\AdvancedArrays;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class ReduceWithArrayReduce extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $data = range(1, 10000);

        $result = array_reduce($data, function ($carry, $item) {
            return $carry + $item;
        }, 0);
    }
}
