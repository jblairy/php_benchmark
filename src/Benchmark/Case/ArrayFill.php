<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\Case;

use Jblairy\PhpBenchmark\Benchmark\AbstractBenchmark;
use Jblairy\PhpBenchmark\PhpVersion\Attribute\Php56;
use Jblairy\PhpBenchmark\PhpVersion\Attribute\Php70;
use Jblairy\PhpBenchmark\PhpVersion\Attribute\Php71;
use Jblairy\PhpBenchmark\PhpVersion\Attribute\Php72;
use Jblairy\PhpBenchmark\PhpVersion\Attribute\Php73;
use Jblairy\PhpBenchmark\PhpVersion\Attribute\Php74;
use Jblairy\PhpBenchmark\PhpVersion\Attribute\Php80;
use Jblairy\PhpBenchmark\PhpVersion\Attribute\Php81;
use Jblairy\PhpBenchmark\PhpVersion\Attribute\Php82;
use Jblairy\PhpBenchmark\PhpVersion\Attribute\Php83;
use Jblairy\PhpBenchmark\PhpVersion\Attribute\Php84;

final class ArrayFill extends AbstractBenchmark
{
    #[Php56]
    #[Php70]
    #[Php71]
    #[Php72]
    #[Php73]
    #[Php74]
    #[Php80]
    #[Php81]
    #[Php82]
    #[Php83]
    #[Php84]
    public function execute(): void
    {
        $arr = array_fill(0, 100000, 'test');
    }
}
