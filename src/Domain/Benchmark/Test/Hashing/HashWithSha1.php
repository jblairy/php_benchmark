<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Hashing;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class HashWithSha1 extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        for ($i = 0; 50000 > $i; ++$i) {
            $result = sha1('test string ' . $i);
        }
    }
}
