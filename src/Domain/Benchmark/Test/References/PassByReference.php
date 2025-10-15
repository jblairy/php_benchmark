<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\References;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class PassByReference extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        <<<PHP
            function increment(&\$value) {
                \$value++;
            }

            \$x = 0;
            for (\$i = 0; \$i < 100000; ++\$i) {
                increment(\$x);
            }
        PHP;
    }
}
