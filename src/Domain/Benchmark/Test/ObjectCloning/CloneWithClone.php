<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\ObjectCloning;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;
use stdClass;

final class CloneWithClone extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $original = new stdClass();
        $original->value = 123;
        $original->name = 'test';

        for ($i = 0; $i < 100000; ++$i) {
            $copy = clone $original;
        }
    }
}
