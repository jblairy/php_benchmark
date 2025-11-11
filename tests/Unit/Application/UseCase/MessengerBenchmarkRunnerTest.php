<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Tests\Unit\Application\UseCase;

use Jblairy\PhpBenchmark\Application\Message\ExecuteBenchmarkMessage;
use Jblairy\PhpBenchmark\Application\UseCase\MessengerBenchmarkRunner;
use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkCompleted;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkStarted;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\EventDispatcherPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\MessageBusPort;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MessengerBenchmarkRunner::class)]
final class MessengerBenchmarkRunnerTest extends TestCase
{
    private MessageBusPort&MockObject $messageBus;

    private EventDispatcherPort&MockObject $eventDispatcher;

    private MessengerBenchmarkRunner $messengerBenchmarkRunner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageBus = $this->createMock(MessageBusPort::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherPort::class);
        $this->messengerBenchmarkRunner = new MessengerBenchmarkRunner(
            $this->messageBus,
        );
    }

    public function testRunDispatchesMessagesForEachIteration(): void
    {
        // Given
        $benchmark = new class () implements Benchmark {
            public function getMethodBody(PhpVersion $phpVersion): string
            {
                return 'return 1;';
            }

            public function getSlug(): string
            {
                return 'test-benchmark';
            }

            public function getWarmupIterations(): ?int
            {
                return null;
            }

            public function getInnerIterations(): ?int
            {
                return null;
            }
        };

        $benchmarkConfiguration = new BenchmarkConfiguration(
            benchmark: $benchmark,
            phpVersion: PhpVersion::PHP_8_4,
            iterations: 3,
        );

        $messagesDispatched = [];
        $this->messageBus
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->willReturnCallback(function (object $message) use (&$messagesDispatched): void {
                $messagesDispatched[] = $message;
            });

        // Expect start and completed events
        $this->eventDispatcher
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->with(self::logicalOr(
                self::isInstanceOf(BenchmarkStarted::class),
                self::isInstanceOf(BenchmarkCompleted::class),
            ));

        // When
        $this->messengerBenchmarkRunner->run($benchmarkConfiguration);

        // Then
        self::assertCount(3, $messagesDispatched);

        foreach ($messagesDispatched as $index => $message) {
            self::assertInstanceOf(ExecuteBenchmarkMessage::class, $message);
            self::assertSame($benchmark::class, $message->benchmarkClass);
            self::assertSame('test-benchmark', $message->benchmarkSlug);
            self::assertSame('TestBenchmark', $message->benchmarkName);
            self::assertSame('php84', $message->phpVersion);
            self::assertSame(3, $message->iterations);
            self::assertSame($index + 1, $message->iterationNumber);
        }
    }
}
