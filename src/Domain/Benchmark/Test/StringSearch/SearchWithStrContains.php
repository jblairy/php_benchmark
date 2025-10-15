<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\StringSearch;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php80;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php81;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php82;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php83;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php84;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php85;

final class SearchWithStrContains extends AbstractBenchmark
{
    #[Php80]
    #[Php81]
    #[Php82]
    #[Php83]
    #[Php84]
    #[Php85]
    public function execute(): void
    {
        $haystack = 'The quick brown fox jumps over the lazy dog';
        for ($i = 0; $i < 100000; ++$i) {
            $result = str_contains($haystack, 'fox');
        }
    }
}
