<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\ObjectOperations;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;
use stdClass;

final class CheckWithIsset extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $obj = new stdClass();
        $obj->value = 123;

        for ($i = 0; $i < 100000; ++$i) {
            $result = isset($obj->value);
        }
    }
}
