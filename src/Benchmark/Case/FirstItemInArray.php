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
use Jblairy\PhpBenchmark\PhpVersion\Attribute\Php85;

final class FirstItemInArray extends AbstractBenchmark
{
    #[Php85]
    public function executeWithPhp85(): void
    {
        $data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        $first = array_first($data);
    }

    #[Php80]
    #[Php81]
    #[Php82]
    #[Php83]
    #[Php84]
    public function executeWithPhp8x(): void
    {
        $data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        $first = $data[array_key_first($data)];
    }

    #[Php56]
    #[Php70]
    #[Php71]
    #[Php72]
    #[Php73]
    #[Php74]
    public function executeWithPhp7AndOlder(): void
    {
        $data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        $first = reset($data);
    }
}
