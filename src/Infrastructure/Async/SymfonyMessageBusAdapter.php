<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Async;

use Jblairy\PhpBenchmark\Domain\Benchmark\Port\MessageBusPort;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Adapter that bridges Domain MessageBusPort to Symfony Messenger.
 *
 * This allows the Domain and Application layers to remain independent
 * of Symfony Messenger while still using its async capabilities.
 */
final readonly class SymfonyMessageBusAdapter implements MessageBusPort
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatch(object $message): void
    {
        $this->messageBus->dispatch($message);
    }
}
