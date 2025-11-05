<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Async;

use Jblairy\PhpBenchmark\Application\Message\ExecuteBenchmarkMessage;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\AsyncExecutorPort;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Symfony Messenger adapter implementing AsyncExecutorPort.
 *
 * Uses Symfony Messenger queue system for production-ready async execution with:
 * - Automatic retry on failure
 * - Persistent queue (survives restarts)
 * - Monitoring and observability
 * - Horizontal scaling with multiple workers
 *
 * This replaces SpatieAsyncExecutorAdapter for better production capabilities.
 */
final class MessengerAsyncExecutorAdapter implements AsyncExecutorPort
{
    private int $dispatchedCount = 0;
    private int $completedCount = 0;

    /** @var array<string, callable> */
    private array $successCallbacks = [];

    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function addTask(callable $task, callable $onSuccess): void
    {
        // Since Messenger is fully async, we need to store the callback
        // and track task completion via events
        $executionId = uniqid('exec_', true);

        // Store success callback for later
        $this->successCallbacks[$executionId] = $onSuccess;

        // For now, we execute synchronously to get the result
        // In a true async system, this would be handled by events
        $result = $task();

        if ($result instanceof BenchmarkResult) {
            $onSuccess($result);
            ++$this->completedCount;
        }

        ++$this->dispatchedCount;
    }

    public function wait(): void
    {
        // With Messenger, we don't need to wait - messages are processed by workers
        // This method is kept for interface compatibility but does nothing
        // All work is done by the worker consuming the async transport

        // Clear callbacks after execution
        $this->successCallbacks = [];
    }

    public function dispatch(ExecuteBenchmarkMessage $message): void
    {
        $this->messageBus->dispatch($message);
        ++$this->dispatchedCount;
    }

    public function getDispatchedCount(): int
    {
        return $this->dispatchedCount;
    }

    public function getCompletedCount(): int
    {
        return $this->completedCount;
    }
}
