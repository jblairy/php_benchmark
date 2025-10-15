<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\StaticVsInstance;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class AccessInstanceProperty extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        <<<PHP
            class TestClass {
                public \$value = 100;
            }

            \$instance = new TestClass();
            for (\$i = 0; \$i < 100000; ++\$i) {
                \$result = \$instance->value;
            }
        PHP;
    }
}
