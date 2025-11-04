<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Tests\Unit\Application\UseCase;

use Jblairy\PhpBenchmark\Application\UseCase\AsyncBenchmarkRunner;
use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkCompleted;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkProgress;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkStarted;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\AsyncExecutorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkExecutorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\EventDispatcherPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ResultPersisterPort;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AsyncBenchmarkRunner::class)]
final class AsyncBenchmarkRunnerTest extends TestCase
{
    private BenchmarkExecutorPort&MockObject $benchmarkExecutor;
    private ResultPersisterPort&MockObject $resultPersister;
    private EventDispatcherPort&MockObject $eventDispatcher;
    private AsyncExecutorPort&MockObject $asyncExecutor;
    private AsyncBenchmarkRunner $runner;

    protected function setUp(): void
    {
        $this->benchmarkExecutor = $this->createMock(BenchmarkExecutorPort::class);
        $this->resultPersister = $this->createMock(ResultPersisterPort::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherPort::class);
        $this->asyncExecutor = $this->createMock(AsyncExecutorPort::class);

        $this->runner = new AsyncBenchmarkRunner(
            benchmarkExecutorPort: $this->benchmarkExecutor,
            resultPersisterPort: $this->resultPersister,
            eventDispatcher: $this->eventDispatcher,
            asyncExecutor: $this->asyncExecutor,
        );
    }

    public function testRunDispatchesBenchmarkStartedEvent(): void
    {
        // Arrange
        $benchmark = $this->createMock(Benchmark::class);

        $configuration = new BenchmarkConfiguration(
            benchmark: $benchmark,
            phpVersion: PhpVersion::PHP_8_4,
            iterations: 1,
        );

        $result = new BenchmarkResult(
            executionTimeMs: 1.0,
            memoryUsedBytes: 100,
            memoryPeakBytes: 200,
        );

        $this->asyncExecutor->method('addTask')->willReturnCallback(
            function (callable $task, callable $onSuccess) use ($result): void {
                $onSuccess($result);
            },
        );

        // Assert BenchmarkStarted event is dispatched
        $dispatchedEvents = [];
        $this->eventDispatcher
            ->expects(self::exactly(3))
            ->method('dispatch')
            ->willReturnCallback(function (object $event) use (&$dispatchedEvents): void {
                $dispatchedEvents[] = $event;
            });

        // Act
        $this->runner->run($configuration);

        // Assert
        self::assertCount(3, $dispatchedEvents);
        self::assertInstanceOf(BenchmarkStarted::class, $dispatchedEvents[0]);
        self::assertInstanceOf(BenchmarkProgress::class, $dispatchedEvents[1]);
        self::assertInstanceOf(BenchmarkCompleted::class, $dispatchedEvents[2]);
    }

    public function testRunExecutesBenchmarkAndPersistsResults(): void
    {
        // Arrange
        $benchmark = $this->createMock(Benchmark::class);

        $configuration = new BenchmarkConfiguration(
            benchmark: $benchmark,
            phpVersion: PhpVersion::PHP_8_4,
            iterations: 3,
        );

        $result = new BenchmarkResult(
            executionTimeMs: 1.23,
            memoryUsedBytes: 1024,
            memoryPeakBytes: 2048,
        );

        $this->benchmarkExecutor
            ->expects(self::never())
            ->method('execute');

        $this->asyncExecutor
            ->expects(self::exactly(3))
            ->method('addTask')
            ->willReturnCallback(function (callable $task, callable $onSuccess) use ($result): void {
                // Simulate async execution
                $onSuccess($result);
            });

        $this->resultPersister
            ->expects(self::exactly(3))
            ->method('persist')
            ->with($configuration, $result);

        $this->asyncExecutor
            ->expects(self::once())
            ->method('wait');

        // Act
        $this->runner->run($configuration);
    }

    public function testRunDispatchesProgressEventsForEachIteration(): void
    {
        // Arrange
        $benchmark = $this->createMock(Benchmark::class);

        $configuration = new BenchmarkConfiguration(
            benchmark: $benchmark,
            phpVersion: PhpVersion::PHP_8_4,
            iterations: 2,
        );

        $result = new BenchmarkResult(
            executionTimeMs: 1.0,
            memoryUsedBytes: 100,
            memoryPeakBytes: 200,
        );

        $this->asyncExecutor->method('addTask')->willReturnCallback(
            function (callable $task, callable $onSuccess) use ($result): void {
                $onSuccess($result);
            },
        );

        $dispatchedEvents = [];
        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnCallback(function (object $event) use (&$dispatchedEvents): void {
                $dispatchedEvents[] = $event;
            });

        // Act
        $this->runner->run($configuration);

        // Assert: Should have BenchmarkStarted + 2 Progress + BenchmarkCompleted
        self::assertCount(4, $dispatchedEvents);
        self::assertInstanceOf(BenchmarkStarted::class, $dispatchedEvents[0]);
        self::assertInstanceOf(BenchmarkProgress::class, $dispatchedEvents[1]);
        self::assertInstanceOf(BenchmarkProgress::class, $dispatchedEvents[2]);
        self::assertInstanceOf(BenchmarkCompleted::class, $dispatchedEvents[3]);
    }

    public function testRunDispatchesCompletedEventAfterAllIterations(): void
    {
        // Arrange
        $benchmark = $this->createMock(Benchmark::class);

        $configuration = new BenchmarkConfiguration(
            benchmark: $benchmark,
            phpVersion: PhpVersion::PHP_8_4,
            iterations: 1,
        );

        $this->asyncExecutor->method('addTask')->willReturnCallback(
            function (callable $task, callable $onSuccess): void {
                $result = new BenchmarkResult(
                    executionTimeMs: 1.0,
                    memoryUsedBytes: 100,
                    memoryPeakBytes: 200,
                );
                $onSuccess($result);
            },
        );

        $completedEventDispatched = false;
        $this->eventDispatcher
            ->method('dispatch')
            ->willReturnCallback(function (object $event) use (&$completedEventDispatched): void {
                if ($event instanceof BenchmarkCompleted) {
                    $completedEventDispatched = true;
                }
            });

        // Act
        $this->runner->run($configuration);

        // Assert
        self::assertTrue($completedEventDispatched);
    }

    public function testRunCallsAsyncExecutorWait(): void
    {
        // Arrange
        $benchmark = $this->createMock(Benchmark::class);

        $configuration = new BenchmarkConfiguration(
            benchmark: $benchmark,
            phpVersion: PhpVersion::PHP_8_4,
            iterations: 1,
        );

        $this->asyncExecutor->method('addTask')->willReturnCallback(
            function (callable $task, callable $onSuccess): void {
                $result = new BenchmarkResult(
                    executionTimeMs: 1.0,
                    memoryUsedBytes: 100,
                    memoryPeakBytes: 200,
                );
                $onSuccess($result);
            },
        );

        $this->asyncExecutor
            ->expects(self::once())
            ->method('wait');

        // Act
        $this->runner->run($configuration);
    }
}
