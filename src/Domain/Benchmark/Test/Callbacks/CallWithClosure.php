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
        for ($i = 0; 100000 > $i; ++$i) {
            $result = (fn ($x): int => $x * 2)($i);
        }
    }
}
