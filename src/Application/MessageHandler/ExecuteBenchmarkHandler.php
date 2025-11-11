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
use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

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
        private BenchmarkExecutorPort $benchmarkExecutorPort,
        private ResultPersisterPort $resultPersisterPort,
        private EventDispatcherPort $eventDispatcherPort,
        private BenchmarkRepositoryPort $benchmarkRepositoryPort,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ExecuteBenchmarkMessage $executeBenchmarkMessage): void
    {
        $timeout = $_ENV['BENCHMARK_TIMEOUT'] ?? 60;
        set_time_limit(is_numeric($timeout) ? (int) $timeout : 60);

        $this->logger->info('Processing benchmark execution', [
            'benchmark' => $executeBenchmarkMessage->benchmarkName,
            'php_version' => $executeBenchmarkMessage->phpVersion,
            'iteration' => $executeBenchmarkMessage->iterationNumber,
            'execution_id' => $executeBenchmarkMessage->executionId,
        ]);

        try {
            // Load benchmark from repository
            $benchmark = $this->benchmarkRepositoryPort->findBenchmarkByName($executeBenchmarkMessage->benchmarkSlug);
            if (null === $benchmark) {
                throw new RuntimeException(sprintf('Benchmark %s not found in repository', $executeBenchmarkMessage->benchmarkSlug));
            }

            $phpVersion = PhpVersion::from($executeBenchmarkMessage->phpVersion);

            // Dispatch start event for first iteration
            if (1 === $executeBenchmarkMessage->iterationNumber) {
                $this->eventDispatcherPort->dispatch(
                    new BenchmarkStarted(
                        benchmarkId: $executeBenchmarkMessage->benchmarkSlug,
                        benchmarkName: $executeBenchmarkMessage->benchmarkName,
                        phpVersion: $executeBenchmarkMessage->phpVersion,
                        totalIterations: $executeBenchmarkMessage->iterations,
                    ),
                );
            }

            $benchmarkConfiguration = new BenchmarkConfiguration(
                benchmark: $benchmark,
                phpVersion: $phpVersion,
                iterations: 1, // Single iteration per message
            );

            // Execute benchmark
            $result = $this->benchmarkExecutorPort->execute($benchmarkConfiguration);

            // Persist result
            $this->resultPersisterPort->persist($benchmarkConfiguration, $result);

            // Dispatch progress event
            $this->eventDispatcherPort->dispatch(
                new BenchmarkProgress(
                    benchmarkId: $executeBenchmarkMessage->benchmarkSlug,
                    benchmarkName: $executeBenchmarkMessage->benchmarkName,
                    phpVersion: $executeBenchmarkMessage->phpVersion,
                    currentIteration: $executeBenchmarkMessage->iterationNumber,
                    totalIterations: $executeBenchmarkMessage->iterations,
                ),
            );

            // Dispatch completed event for last iteration
            if ($executeBenchmarkMessage->iterationNumber === $executeBenchmarkMessage->iterations) {
                $this->eventDispatcherPort->dispatch(
                    new BenchmarkCompleted(
                        benchmarkId: $executeBenchmarkMessage->benchmarkSlug,
                        benchmarkName: $executeBenchmarkMessage->benchmarkName,
                        phpVersion: $executeBenchmarkMessage->phpVersion,
                        totalIterations: $executeBenchmarkMessage->iterations,
                    ),
                );
            }

            $this->logger->info('Benchmark execution completed', [
                'benchmark' => $executeBenchmarkMessage->benchmarkName,
                'php_version' => $executeBenchmarkMessage->phpVersion,
                'execution_time_ms' => $result->executionTimeMs,
                'memory_usage_bytes' => $result->memoryUsedBytes,
            ]);
        } catch (Throwable $throwable) {
            $this->logger->error('Benchmark execution failed', [
                'benchmark' => $executeBenchmarkMessage->benchmarkName,
                'php_version' => $executeBenchmarkMessage->phpVersion,
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            throw $throwable;
        }
    }
}
