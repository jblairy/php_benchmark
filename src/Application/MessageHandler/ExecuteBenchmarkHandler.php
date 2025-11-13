<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\MessageHandler;

use Jblairy\PhpBenchmark\Application\Message\ExecuteBenchmarkMessage;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkExecutorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\LoggerPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ResultPersisterPort;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use RuntimeException;
use Throwable;

/**
 * Handles asynchronous benchmark execution via message bus.
 *
 * This handler processes ExecuteBenchmarkMessage from the async queue,
 * executes the benchmark, and persists results.
 *
 * Configuration: Tagged as message handler in services.yaml to keep
 * Application layer independent of Symfony Messenger infrastructure.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects") - Message handler coordinates multiple domain services
 */
final readonly class ExecuteBenchmarkHandler
{
    public function __construct(
        private BenchmarkExecutorPort $benchmarkExecutorPort,
        private ResultPersisterPort $resultPersisterPort,
        private BenchmarkRepositoryPort $benchmarkRepositoryPort,
        private LoggerPort $logger,
        private int $benchmarkTimeout = 60,
    ) {
    }

    public function __invoke(ExecuteBenchmarkMessage $executeBenchmarkMessage): void
    {
        set_time_limit($this->benchmarkTimeout);

        $this->logger->info('Processing benchmark execution', [
            'benchmark' => $executeBenchmarkMessage->benchmarkName,
            'php_version' => $executeBenchmarkMessage->phpVersion,
            'iteration' => $executeBenchmarkMessage->iterationNumber,
            'execution_id' => $executeBenchmarkMessage->executionId,
        ]);

        try {
            $benchmark = $this->benchmarkRepositoryPort->findBenchmarkByName($executeBenchmarkMessage->benchmarkSlug);
            if (null === $benchmark) {
                throw new RuntimeException(sprintf('Benchmark %s not found in repository', $executeBenchmarkMessage->benchmarkSlug));
            }

            $phpVersion = PhpVersion::from($executeBenchmarkMessage->phpVersion);

            $benchmarkConfiguration = new BenchmarkConfiguration(
                benchmark: $benchmark,
                phpVersion: $phpVersion,
                iterations: 1,
            );

            $result = $this->benchmarkExecutorPort->execute($benchmarkConfiguration);

            $this->resultPersisterPort->persist($benchmarkConfiguration, $result);

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
