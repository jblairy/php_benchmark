<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Regex;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class MatchAllWithPregMatchAll extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $text = 'Numbers: 123, 456, 789, 012, 345';

        for ($i = 0; 50000 > $i; ++$i) {
            preg_match_all('/\d+/', $text, $matches);
        }
    }
}
