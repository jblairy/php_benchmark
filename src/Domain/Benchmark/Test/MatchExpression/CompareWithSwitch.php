<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\MatchExpression;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class CompareWithSwitch extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        for ($i = 0; 100000 > $i; ++$i) {
            $value = $i % 5;
            $result = match ($value) {
                0 => 'zero',
                1 => 'one',
                2 => 'two',
                3 => 'three',
                default => 'other',
            };
        }
    }
}
