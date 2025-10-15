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
        for ($i = 0; $i < 100000; ++$i) {
            $value = $i % 5;
            switch ($value) {
                case 0:
                    $result = 'zero';
                    break;
                case 1:
                    $result = 'one';
                    break;
                case 2:
                    $result = 'two';
                    break;
                case 3:
                    $result = 'three';
                    break;
                default:
                    $result = 'other';
            }
        }
    }
}
