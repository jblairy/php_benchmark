<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\StringConcatenation;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class ConcatenationWithImplode extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $result = '';
        for ($i = 0; $i < 10000; ++$i) {
            $result = implode(' ', ['Hello', 'World', $i, 'test', 'benchmark']);
        }
    }
}
