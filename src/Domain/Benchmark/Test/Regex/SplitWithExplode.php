<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Regex;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class SplitWithExplode extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $text = 'one,two,three,four,five';

        for ($i = 0; $i < 50000; ++$i) {
            $result = explode(',', $text);
        }
    }
}
