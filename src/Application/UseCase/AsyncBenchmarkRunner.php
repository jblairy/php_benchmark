<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\UseCase;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkExecutorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ResultPersisterPort;
use Spatie\Async\Pool;

final class AsyncBenchmarkRunner
{
    private const DEFAULT_CONCURRENCY = 100;

    public function __construct(
        private readonly BenchmarkExecutorPort $executor,
        private readonly ResultPersisterPort $persister,
        private readonly int $concurrency = self::DEFAULT_CONCURRENCY,
    ) {
    }

    public function run(BenchmarkConfiguration $configuration): void
    {
        $pool = Pool::create()->concurrency($this->concurrency);

        for ($i = 0; $i < $configuration->iterations; ++$i) {
            $pool->add(function () use ($configuration) {
                return $this->executor->execute($configuration);
            })->then(function ($result) use ($configuration) {
                $this->persister->persist($configuration, $result);
            });
        }

        $pool->wait();
    }
}
