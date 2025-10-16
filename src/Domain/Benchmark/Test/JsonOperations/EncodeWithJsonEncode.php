<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test\JsonOperations;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class EncodeWithJsonEncode extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
            'items' => range(1, 100),
        ];

        for ($i = 0; 10000 > $i; ++$i) {
            $result = json_encode($data);
        }
    }
}
