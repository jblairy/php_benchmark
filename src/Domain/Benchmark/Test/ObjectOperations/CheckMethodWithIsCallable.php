<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\ObjectOperations;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class CheckMethodWithIsCallable extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        <<<PHP
            class TestClass {
                public function testMethod() {
                    return true;
                }
            }

            \$obj = new TestClass();
            for (\$i = 0; \$i < 100000; ++\$i) {
                \$result = is_callable([\$obj, 'testMethod']);
            }
        PHP;
    }
}
