<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\AdvancedStrings;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class LengthWithStrlen extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $text = 'Hello World, this is a test string for benchmarking';

        for ($i = 0; 100000 > $i; ++$i) {
            $result = mb_strlen($text);
        }
    }
}
