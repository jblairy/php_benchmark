<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\NullCoalescing;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class CheckWithIssetTernary extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $data = ['key1' => 'value1', 'key2' => null, 'key3' => 'value3'];

        for ($i = 0; $i < 100000; ++$i) {
            $result = isset($data['key2']) ? $data['key2'] : 'default';
        }
    }
}
