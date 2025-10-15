<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Sorting;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class SortWithSort extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        for ($i = 0; $i < 1000; ++$i) {
            $data = range(1, 1000);
            shuffle($data);
            sort($data);
        }
    }
}
