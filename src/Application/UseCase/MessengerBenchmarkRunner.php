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
        $benchmarkSlug = $benchmarkConfiguration->benchmark->getSlug();
        $phpVersion = $benchmarkConfiguration->phpVersion->value;
        $totalIterations = $benchmarkConfiguration->iterations;
        $executionId = uniqid('exec_', true);

        // Start event will be dispatched by the first message handler

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

        // Completion event will be dispatched by the last message handler
    }
}