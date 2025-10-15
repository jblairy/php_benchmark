<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\StaticVsInstance;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class CallStaticMethod extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        <<<PHP
            class TestClass {
                public static function compute(\$x) {
                    return \$x * 2;
                }
            }

            for (\$i = 0; \$i < 100000; ++\$i) {
                \$result = TestClass::compute(\$i);
            }
        PHP;
    }
}
