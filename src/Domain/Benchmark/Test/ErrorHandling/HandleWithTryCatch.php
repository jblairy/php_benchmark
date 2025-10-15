<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\ErrorHandling;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class HandleWithTryCatch extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        for ($i = 0; $i < 10000; ++$i) {
            try {
                $result = 100 / ($i % 10 === 0 ? 1 : $i);
            } catch (\Throwable $e) {
                $result = 0;
            }
        }
    }
}
