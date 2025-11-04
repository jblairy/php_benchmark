<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Async;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\AsyncExecutorPort;
use Spatie\Async\Pool;

/**
 * Spatie\Async adapter implementing AsyncExecutorPort.
 *
 * Adapts Spatie\Async\Pool to our domain interface following the Adapter pattern.
 * This allows the domain to remain library-agnostic.
 */
final class SpatieAsyncExecutorAdapter implements AsyncExecutorPort
{
    private Pool $pool;

    public function __construct(
        private readonly int $concurrency = 100,
    ) {
        $this->pool = Pool::create()->concurrency($this->concurrency);
    }

    public function addTask(callable $task, callable $onSuccess): void
    {
        $this->pool
            ->add(fn (): BenchmarkResult => $task())
            ->then(function (BenchmarkResult $result) use ($onSuccess): void {
                $onSuccess($result);
            });
    }

    public function wait(): void
    {
        $this->pool->wait();
    }
}
