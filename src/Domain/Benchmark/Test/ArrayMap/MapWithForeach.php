<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\ArrayMap;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class MapWithForeach extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $data = range(1, 10000);
        $result = [];

        foreach ($data as $item) {
            $result[] = $item * 2;
        }
    }
}
