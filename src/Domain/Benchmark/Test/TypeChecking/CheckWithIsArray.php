<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\TypeChecking;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class CheckWithIsArray extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $data = [1, 2, 3, 4, 5];

        for ($i = 0; 100000 > $i; ++$i) {
            $result = is_array($data);
        }
    }
}
