<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Php8Features;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class StrStartsWithSubstr extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $text = 'Hello World';

        for ($i = 0; 100000 > $i; ++$i) {
            $result = 'Hello' === mb_substr($text, 0, 5);
        }
    }
}
