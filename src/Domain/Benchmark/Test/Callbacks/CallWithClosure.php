<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Callbacks;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php70;

final class CallWithClosure extends AbstractBenchmark
{
    #[Php70]
    public function execute(): void
    {
        for ($i = 0; $i < 100000; ++$i) {
            $result = (function ($x) {
                return $x * 2;
            })($i);
        }
    }
}
