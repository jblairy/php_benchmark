<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\StringConcatenation;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class ConcatenationWithDot extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $result = '';
        for ($i = 0; 10000 > $i; ++$i) {
            $result = 'Hello World ' . $i . ' test benchmark';
        }
    }
}
