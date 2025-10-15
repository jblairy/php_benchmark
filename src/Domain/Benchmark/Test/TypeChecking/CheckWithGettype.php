<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\TypeChecking;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class CheckWithGettype extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $data = [1, 2, 3, 4, 5];

        for ($i = 0; $i < 100000; ++$i) {
            $result = gettype($data) === 'array';
        }
    }
}
