<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\ErrorHandling;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;
use Throwable;

final class HandleWithTryCatch extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        for ($i = 0; 10000 > $i; ++$i) {
            try {
                $result = 100 / (0 === $i % 10 ? 1 : $i);
            } catch (Throwable) {
                $result = 0;
            }
        }
    }
}
