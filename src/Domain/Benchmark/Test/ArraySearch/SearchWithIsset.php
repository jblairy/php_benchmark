<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\ArraySearch;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class SearchWithIsset extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $haystack = array_flip(range(1, 1000));

        for ($i = 0; $i < 10000; ++$i) {
            $result = isset($haystack[750]);
        }
    }
}
