<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\VariableOptimization;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class DefineWithDefine extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        <<<PHP
            define('TEST_VALUE', 100);

            for (\$i = 0; \$i < 100000; ++\$i) {
                \$result = TEST_VALUE;
            }
        PHP;
    }
}
