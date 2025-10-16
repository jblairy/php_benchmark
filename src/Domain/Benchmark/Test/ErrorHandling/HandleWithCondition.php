<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\ErrorHandling;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class HandleWithCondition extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        for ($i = 0; 10000 > $i; ++$i) {
            $divisor = 0 === $i % 10 ? 1 : $i;
            $result = 0 !== $divisor ? 100 / $divisor : 0;
        }
    }
}
