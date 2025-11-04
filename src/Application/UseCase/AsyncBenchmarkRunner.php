<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\UseCase;

use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkCompleted;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkProgress;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkStarted;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\AsyncExecutorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkExecutorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\EventDispatcherPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ResultPersisterPort;

final readonly class AsyncBenchmarkRunner
{
    public function __construct(
        private BenchmarkExecutorPort $benchmarkExecutorPort,
        private ResultPersisterPort $resultPersisterPort,
        private EventDispatcherPort $eventDispatcher,
        private AsyncExecutorPort $asyncExecutor,
    ) {
    }

    public function run(BenchmarkConfiguration $benchmarkConfiguration): void
    {
        $benchmarkId = $benchmarkConfiguration->benchmark::class;
        $benchmarkName = $benchmarkConfiguration->getBenchmarkName();
        $phpVersion = $benchmarkConfiguration->phpVersion->value;
        $totalIterations = $benchmarkConfiguration->iterations;

        $this->eventDispatcher->dispatch(
            new BenchmarkStarted(
                benchmarkId: $benchmarkId,
                benchmarkName: $benchmarkName,
                phpVersion: $phpVersion,
                totalIterations: $totalIterations,
            ),
        );

        $completedIterations = 0;
        $results = [];

        for ($i = 0; $i < $benchmarkConfiguration->iterations; ++$i) {
            $this->asyncExecutor->addTask(
                task: fn (): \Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult => $this->benchmarkExecutorPort->execute($benchmarkConfiguration),
                onSuccess: function (\Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult $result) use ($benchmarkConfiguration, &$completedIterations, &$results, $benchmarkId, $benchmarkName, $phpVersion, $totalIterations): void {
                    $this->resultPersisterPort->persist($benchmarkConfiguration, $result);
                    $results[] = $result;
                    ++$completedIterations;

                    $this->eventDispatcher->dispatch(
                        new BenchmarkProgress(
                            benchmarkId: $benchmarkId,
                            benchmarkName: $benchmarkName,
                            phpVersion: $phpVersion,
                            currentIteration: $completedIterations,
                            totalIterations: $totalIterations,
                        ),
                    );
                },
            );
        }

        $this->asyncExecutor->wait();

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
