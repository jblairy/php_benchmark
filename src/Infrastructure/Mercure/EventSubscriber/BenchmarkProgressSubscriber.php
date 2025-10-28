<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Mercure\EventSubscriber;

use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkCompleted;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkProgress;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkStarted;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Infrastructure adapter: publishes benchmark progress events to Mercure hub.
 */
final readonly class BenchmarkProgressSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private HubInterface $hub,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            BenchmarkStarted::class => 'onBenchmarkStarted',
            BenchmarkProgress::class => 'onBenchmarkProgress',
            BenchmarkCompleted::class => 'onBenchmarkCompleted',
        ];
    }

    public function onBenchmarkStarted(BenchmarkStarted $event): void
    {
        $this->publishUpdate('benchmark/progress', $event->toArray());
    }

    public function onBenchmarkProgress(BenchmarkProgress $event): void
    {
        $this->publishUpdate('benchmark/progress', $event->toArray());
    }

    public function onBenchmarkCompleted(BenchmarkCompleted $event): void
    {
        $this->publishUpdate('benchmark/progress', $event->toArray());
        $this->publishUpdate('benchmark/results', $event->toArray());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function publishUpdate(string $topic, array $data): void
    {
        $update = new Update(
            $topic,
            json_encode($data, JSON_THROW_ON_ERROR),
        );

        $this->hub->publish($update);
    }
}
