<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\StringSearch;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class SearchWithStrpos extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $haystack = 'The quick brown fox jumps over the lazy dog';
        for ($i = 0; $i < 100000; ++$i) {
            $result = strpos($haystack, 'fox') !== false;
        }
    }
}
