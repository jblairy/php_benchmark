<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Callbacks;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class CallWithDirectInvocation extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $func = (fn ($x): int|float => $x * 2);

        for ($i = 0; 100000 > $i; ++$i) {
            $result = $func($i);
        }
    }
}
