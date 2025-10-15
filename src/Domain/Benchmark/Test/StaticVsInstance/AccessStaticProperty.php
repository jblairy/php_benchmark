<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\StaticVsInstance;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class AccessStaticProperty extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        <<<PHP
            class TestClass {
                public static \$value = 100;
            }

            for (\$i = 0; \$i < 100000; ++\$i) {
                \$result = TestClass::\$value;
            }
        PHP;
    }
}
