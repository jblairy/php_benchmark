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
            public function code(): void {}
            public function name(): string { return 'Test Benchmark'; }
            public function slug(): string { return 'test-benchmark'; }
            public function nameFr(): string { return 'Benchmark de test'; }
            public function descriptionFr(): string { return 'Description du test'; }
            public function descriptionEn(): string { return 'Test description'; }
            public function tags(): array { return []; }
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
            $this->assertEquals('Test Benchmark', $message->benchmarkName);
            $this->assertEquals('php84', $message->phpVersion);
            $this->assertEquals(3, $message->iterations);
            $this->assertEquals($index + 1, $message->iterationNumber);
        }
    }
}