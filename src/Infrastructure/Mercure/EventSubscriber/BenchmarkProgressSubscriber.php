<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Mercure\EventSubscriber;

use Exception;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkCompleted;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkProgress;
use Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkStarted;
use Psr\Log\LoggerInterface;
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
        private LoggerInterface $logger,
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

    public function onBenchmarkStarted(BenchmarkStarted $benchmarkStarted): void
    {
        $this->publishUpdate('benchmark/progress', $benchmarkStarted->toArray());
    }

    public function onBenchmarkProgress(BenchmarkProgress $benchmarkProgress): void
    {
        $this->publishUpdate('benchmark/progress', $benchmarkProgress->toArray());
    }

    public function onBenchmarkCompleted(BenchmarkCompleted $benchmarkCompleted): void
    {
        $this->publishUpdate('benchmark/progress', $benchmarkCompleted->toArray());
        $this->publishUpdate('benchmark/results', $benchmarkCompleted->toArray());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function publishUpdate(string $topic, array $data): void
    {
        try {
            $update = new Update(
                $topic,
                json_encode($data, JSON_THROW_ON_ERROR),
            );

            $this->hub->publish($update);
        } catch (Exception $e) {
            $this->logger->error('Failed to publish Mercure update', [
                'topic' => $topic,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            // Don't re-throw - allow benchmark to continue even if Mercure fails
        }
    }
}
