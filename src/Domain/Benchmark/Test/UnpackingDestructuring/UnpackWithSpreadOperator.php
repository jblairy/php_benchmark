<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\UnpackingDestructuring;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class UnpackWithSpreadOperator extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        <<<PHP
            function sum(...\$numbers) {
                return array_sum(\$numbers);
            }

            \$data = range(1, 100);
            for (\$i = 0; \$i < 1000; ++\$i) {
                \$result = sum(...\$data);
            }
        PHP;
    }
}
