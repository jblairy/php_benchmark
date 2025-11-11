<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\UseCase;

use Jblairy\PhpBenchmark\Application\Message\ExecuteBenchmarkMessage;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkCompleted;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkStarted;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\EventDispatcherPort;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Runs benchmarks asynchronously using Symfony Messenger.
 *
 * This replaces AsyncBenchmarkRunner and uses the Messenger component
 * to dispatch benchmark executions to async queues.
 */
final readonly class MessengerBenchmarkRunner
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private EventDispatcherPort $eventDispatcher,
    ) {
    }

    public function run(BenchmarkConfiguration $benchmarkConfiguration): void
    {
        $benchmarkId = $benchmarkConfiguration->benchmark::class;
        $benchmarkName = $benchmarkConfiguration->getBenchmarkName();
        $benchmarkSlug = $benchmarkConfiguration->benchmark->slug();
        $phpVersion = $benchmarkConfiguration->phpVersion->value;
        $totalIterations = $benchmarkConfiguration->iterations;
        $executionId = uniqid('exec_', true);

        // Dispatch start event
        $this->eventDispatcher->dispatch(
            new BenchmarkStarted(
                benchmarkId: $benchmarkId,
                benchmarkName: $benchmarkName,
                phpVersion: $phpVersion,
                totalIterations: $totalIterations,
            ),
        );

        // Dispatch each iteration as a separate message
        for ($i = 1; $i <= $totalIterations; ++$i) {
            $message = new ExecuteBenchmarkMessage(
                benchmarkClass: $benchmarkId,
                benchmarkSlug: $benchmarkSlug,
                benchmarkName: $benchmarkName,
                phpVersion: $phpVersion,
                iterations: $totalIterations,
                executionId: $executionId,
                iterationNumber: $i,
            );

            $this->messageBus->dispatch($message);
        }

        // Note: The completion event will be dispatched by a separate process
        // that monitors when all iterations are complete. For now, we dispatch
        // it immediately for compatibility, but this should be handled by
        // a completion monitor in a production system.
        $this->eventDispatcher->dispatch(
            new BenchmarkCompleted(
                benchmarkId: $benchmarkId,
                benchmarkName: $benchmarkName,
                phpVersion: $phpVersion,
                totalIterations: $totalIterations,
            ),
        );
    }
}