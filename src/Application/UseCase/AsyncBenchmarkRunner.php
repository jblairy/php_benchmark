<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\UseCase;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkExecutorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ResultPersisterPort;
use Spatie\Async\Pool;

final readonly class AsyncBenchmarkRunner
{
    private const int DEFAULT_CONCURRENCY = 100;

    public function __construct(
        private BenchmarkExecutorPort $benchmarkExecutorPort,
        private ResultPersisterPort $resultPersisterPort,
        private int $concurrency = self::DEFAULT_CONCURRENCY,
    ) {
    }

    public function run(BenchmarkConfiguration $benchmarkConfiguration): void
    {
        $pool = Pool::create()->concurrency($this->concurrency);

        for ($i = 0; $i < $benchmarkConfiguration->iterations; ++$i) {
            $pool->add(fn (): \Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult => $this->benchmarkExecutorPort->execute($benchmarkConfiguration))->then(function ($result) use ($benchmarkConfiguration): void {
                $this->resultPersisterPort->persist($benchmarkConfiguration, $result);
            });
        }

        $pool->wait();
    }
}
