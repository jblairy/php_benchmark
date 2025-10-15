<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\Php8Features;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class NullsafeWithIssetCheck extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        <<<PHP
            class User {
                public \$address = null;
            }

            \$user = new User();
            for (\$i = 0; \$i < 100000; ++\$i) {
                \$result = isset(\$user->address) ? \$user->address : null;
            }
        PHP;
    }
}
