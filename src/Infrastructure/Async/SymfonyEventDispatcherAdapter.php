<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Async;

use Jblairy\PhpBenchmark\Domain\Benchmark\Port\EventDispatcherPort;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Symfony adapter implementing EventDispatcherPort.
 *
 * Adapts Symfony's EventDispatcher to our domain interface following the Adapter pattern.
 * This allows the domain to remain framework-agnostic.
 */
final readonly class SymfonyEventDispatcherAdapter implements EventDispatcherPort
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function dispatch(object $event): void
    {
        $this->eventDispatcher->dispatch($event);
    }
}
