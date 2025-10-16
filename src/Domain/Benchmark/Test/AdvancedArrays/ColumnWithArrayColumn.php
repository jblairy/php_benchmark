<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\AdvancedArrays;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class ColumnWithArrayColumn extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $data = [];
        for ($i = 0; 1000 > $i; ++$i) {
            $data[] = ['id' => $i, 'name' => 'User' . $i, 'email' => 'user' . $i . '@test.com'];
        }
    }
}
