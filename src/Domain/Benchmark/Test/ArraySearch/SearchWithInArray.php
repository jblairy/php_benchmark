<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\ArraySearch;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class SearchWithInArray extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $haystack = range(1, 1000);

        for ($i = 0; 10000 > $i; ++$i) {
            $result = in_array(750, $haystack, true);
        }
    }
}
