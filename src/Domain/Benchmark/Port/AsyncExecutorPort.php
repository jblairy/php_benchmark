<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;

/**
 * Port for asynchronous task execution.
 *
 * This interface abstracts async execution to follow the Dependency Inversion Principle.
 * Infrastructure will provide concrete implementations (e.g., Spatie\Async, ReactPHP, Amp).
 */
interface AsyncExecutorPort
{
    /**
     * Adds a task to the async pool.
     *
     * @param callable(): BenchmarkResult     $task      The task to execute asynchronously
     * @param callable(BenchmarkResult): void $onSuccess Callback executed when task completes successfully
     */
    public function addTask(callable $task, callable $onSuccess): void;

    /**
     * Waits for all tasks to complete.
     */
    public function wait(): void;
}
