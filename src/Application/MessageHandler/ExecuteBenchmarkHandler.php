<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\MessageHandler;

use Jblairy\PhpBenchmark\Application\Message\ExecuteBenchmarkMessage;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkCompleted;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkProgress;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkStarted;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkExecutorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\EventDispatcherPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ResultPersisterPort;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles asynchronous benchmark execution via Symfony Messenger.
 *
 * This handler processes ExecuteBenchmarkMessage from the async queue,
 * executes the benchmark, persists results, and dispatches progress events.
 */
#[AsMessageHandler]
final readonly class ExecuteBenchmarkHandler
{
    public function __construct(
        private BenchmarkExecutorPort $benchmarkExecutor,
        private ResultPersisterPort $resultPersister,
        private EventDispatcherPort $eventDispatcher,
        private BenchmarkRepositoryPort $benchmarkRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ExecuteBenchmarkMessage $message): void
    {
        set_time_limit((int) ($_ENV['BENCHMARK_TIMEOUT'] ?? 60));

        $this->logger->info('Processing benchmark execution', [
            'benchmark' => $message->benchmarkName,
            'php_version' => $message->phpVersion,
            'iteration' => $message->iterationNumber,
            'execution_id' => $message->executionId,
        ]);

        try {
            // Load benchmark from repository
            $benchmark = $this->benchmarkRepository->findBenchmarkByName($message->benchmarkSlug);
            if (!$benchmark) {
                throw new \RuntimeException(sprintf('Benchmark %s not found in repository', $message->benchmarkSlug));
            }

            $phpVersion = PhpVersion::from($message->phpVersion);

            // Dispatch start event for first iteration
            if ($message->iterationNumber === 1) {
                $this->eventDispatcher->dispatch(
                    new BenchmarkStarted(
                        benchmarkId: $message->benchmarkSlug,
                        benchmarkName: $message->benchmarkName,
                        phpVersion: $message->phpVersion,
                        totalIterations: $message->iterations,
                    ),
                );
            }

            $configuration = new BenchmarkConfiguration(
                benchmark: $benchmark,
                phpVersion: $phpVersion,
                iterations: 1, // Single iteration per message
            );

            // Execute benchmark
            $result = $this->benchmarkExecutor->execute($configuration);

            // Persist result
            $this->resultPersister->persist($configuration, $result);

            // Dispatch progress event
            $this->eventDispatcher->dispatch(
                new BenchmarkProgress(
                    benchmarkId: $message->benchmarkSlug,
                    benchmarkName: $message->benchmarkName,
                    phpVersion: $message->phpVersion,
                    currentIteration: $message->iterationNumber,
                    totalIterations: $message->iterations,
                ),
            );

            // Dispatch completed event for last iteration
            if ($message->iterationNumber === $message->iterations) {
                $this->eventDispatcher->dispatch(
                    new BenchmarkCompleted(
                        benchmarkId: $message->benchmarkSlug,
                        benchmarkName: $message->benchmarkName,
                        phpVersion: $message->phpVersion,
                        totalIterations: $message->iterations,
                    ),
                );
            }

            $this->logger->info('Benchmark execution completed', [
                'benchmark' => $message->benchmarkName,
                'php_version' => $message->phpVersion,
                'execution_time_ms' => $result->executionTimeMs,
                'memory_usage_bytes' => $result->memoryUsedBytes,
            ]);
        } catch (\Throwable $throwable) {
            $this->logger->error('Benchmark execution failed', [
                'benchmark' => $message->benchmarkName,
                'php_version' => $message->phpVersion,
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            throw $throwable;
        }
    }
}
