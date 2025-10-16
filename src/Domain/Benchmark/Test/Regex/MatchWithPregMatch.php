<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Regex;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class MatchWithPregMatch extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $text = 'Email: test@example.com, Phone: 123-456-7890';

        for ($i = 0; 50000 > $i; ++$i) {
            preg_match('/[\w.-]+@[\w.-]+\.\w+/', $text, $matches);
        }
    }
}
