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
        $func = function ($x) {
            return $x * 2;
        };

        for ($i = 0; $i < 100000; ++$i) {
            $result = $func($i);
        }
    }
}
