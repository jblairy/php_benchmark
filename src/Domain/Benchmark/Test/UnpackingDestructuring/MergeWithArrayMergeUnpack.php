<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\UnpackingDestructuring;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php74;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php80;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php81;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php82;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php83;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php84;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php85;

final class MergeWithArrayMergeUnpack extends AbstractBenchmark
{
    #[Php74]
    #[Php80]
    #[Php81]
    #[Php82]
    #[Php83]
    #[Php84]
    #[Php85]
    public function execute(): void
    {
        $array1 = range(1, 50);
        $array2 = range(51, 100);
        $array3 = range(101, 150);

        for ($i = 0; $i < 1000; ++$i) {
            $result = [...$array1, ...$array2, ...$array3];
        }
    }
}
