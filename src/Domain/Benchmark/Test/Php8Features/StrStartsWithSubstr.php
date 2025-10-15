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

        for ($i = 0; $i < 100000; ++$i) {
            $result = substr($text, 0, 5) === 'Hello';
        }
    }
}
