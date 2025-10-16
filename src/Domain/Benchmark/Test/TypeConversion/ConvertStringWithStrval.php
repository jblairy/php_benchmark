<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\TypeConversion;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class ConvertStringWithStrval extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        for ($i = 0; 100000 > $i; ++$i) {
            $result = (string) 123;
        }
    }
}
