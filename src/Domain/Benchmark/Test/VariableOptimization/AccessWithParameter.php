<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\VariableOptimization;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class AccessWithParameter extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        <<<PHP
            \$value = 100;

            function testFunction(\$param) {
                return \$param;
            }

            for (\$i = 0; \$i < 100000; ++\$i) {
                \$result = testFunction(\$value);
            }
        PHP;
    }
}
