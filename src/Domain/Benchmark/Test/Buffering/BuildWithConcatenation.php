<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Buffering;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class BuildWithConcatenation extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $result = '';
        for ($i = 0; $i < 1000; ++$i) {
            $result .= 'Line ' . $i . "\n";
        }
    }
}
