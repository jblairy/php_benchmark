<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Buffering;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class BuildWithArrayImplode extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $lines = [];
        for ($i = 0; $i < 1000; ++$i) {
            $lines[] = 'Line ' . $i;
        }
        $result = implode("\n", $lines);
    }
}
