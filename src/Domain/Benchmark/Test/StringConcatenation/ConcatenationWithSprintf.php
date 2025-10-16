<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\StringConcatenation;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class ConcatenationWithSprintf extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $result = '';
        for ($i = 0; 10000 > $i; ++$i) {
            $result = sprintf('%s %s %d %s %s', 'Hello', 'World', $i, 'test', 'benchmark');
        }
    }
}
