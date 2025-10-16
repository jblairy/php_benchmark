<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\StringReplace;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class ReplaceWithStrReplace extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $text = 'Hello World, this is a test string for benchmarking purposes';
        for ($i = 0; 100000 > $i; ++$i) {
            $result = str_replace('test', 'sample', $text);
        }
    }
}
