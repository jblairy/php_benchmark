<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\ValueExtraction;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class ExtractWithList extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $data = [1, 2, 3];

        for ($i = 0; 100000 > $i; ++$i) {
            [$a, $b, $c] = $data;
        }
    }
}
