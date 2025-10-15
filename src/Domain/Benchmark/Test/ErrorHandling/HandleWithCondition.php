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
        for ($i = 0; $i < 10000; ++$i) {
            $divisor = $i % 10 === 0 ? 1 : $i;
            $result = $divisor !== 0 ? 100 / $divisor : 0;
        }
    }
}
