<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Buffering;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class BuildWithOutputBuffer extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        ob_start();
        for ($i = 0; 1000 > $i; ++$i) {
            echo 'Line ' . $i . "\n";
        }

        ob_get_clean();
    }
}
