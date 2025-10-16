<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\NullCoalescing;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php70;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php71;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php72;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php73;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php74;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php80;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php81;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php82;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php83;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php84;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php85;

final class CheckWithNullCoalescing extends AbstractBenchmark
{
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
    #[Php85]
    public function execute(): void
    {
        $data = ['key1' => 'value1', 'key2' => null, 'key3' => 'value3'];

        for ($i = 0; 100000 > $i; ++$i) {
            $result = $data['key2'] ?? 'default';
        }
    }
}
