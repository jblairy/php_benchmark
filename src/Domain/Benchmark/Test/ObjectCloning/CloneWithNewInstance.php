<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\ObjectCloning;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;
use stdClass;

final class CloneWithNewInstance extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        for ($i = 0; $i < 100000; ++$i) {
            $copy = new stdClass();
            $copy->value = 123;
            $copy->name = 'test';
        }
    }
}
