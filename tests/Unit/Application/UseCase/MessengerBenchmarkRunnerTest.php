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
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(MessengerBenchmarkRunner::class)]
final class MessengerBenchmarkRunnerTest extends TestCase
{
    private MessageBusInterface&MockObject $messageBus;
    private EventDispatcherPort&MockObject $eventDispatcher;
    private MessengerBenchmarkRunner $runner;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherPort::class);
        $this->runner = new MessengerBenchmarkRunner(
            $this->messageBus,
            $this->eventDispatcher
        );
    }

    public function testRunDispatchesMessagesForEachIteration(): void
    {
        // Given
        $benchmark = new class implements Benchmark {
            public function getMethodBody(PhpVersion $phpVersion): string {
                return 'return 1;';
            }
            
            public function getSlug(): string {
                return 'test-benchmark';
            }
        };
        
        $configuration = new BenchmarkConfiguration(
            benchmark: $benchmark,
            phpVersion: PhpVersion::PHP_84,
            iterations: 3
        );

        $messagesDispatched = [];
        $this->messageBus
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->willReturnCallback(function ($message) use (&$messagesDispatched) {
                $messagesDispatched[] = $message;
                return new Envelope($message);
            });

        // Expect start and completed events
        $this->eventDispatcher
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->with($this->logicalOr(
                $this->isInstanceOf(BenchmarkStarted::class),
                $this->isInstanceOf(BenchmarkCompleted::class)
            ));

        // When
        $this->runner->run($configuration);

        // Then
        $this->assertCount(3, $messagesDispatched);
        
        foreach ($messagesDispatched as $index => $message) {
            $this->assertInstanceOf(ExecuteBenchmarkMessage::class, $message);
            $this->assertEquals($benchmark::class, $message->benchmarkClass);
            $this->assertEquals('test-benchmark', $message->benchmarkSlug);
            $this->assertEquals('TestBenchmark', $message->benchmarkName);
            $this->assertEquals('php84', $message->phpVersion);
            $this->assertEquals(3, $message->iterations);
            $this->assertEquals($index + 1, $message->iterationNumber);
        }
    }
}