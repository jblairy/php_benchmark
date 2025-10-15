<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\VariableOptimization;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class AccessWithGlobal extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        <<<PHP
            \$globalValue = 100;

            function testFunction() {
                global \$globalValue;
                return \$globalValue;
            }

            for (\$i = 0; \$i < 100000; ++\$i) {
                \$result = testFunction();
            }
        PHP;
    }
}
