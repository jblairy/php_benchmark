<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

/**
 * Port for dispatching domain events.
 *
 * This interface abstracts event dispatching to follow the Dependency Inversion Principle.
 * Infrastructure will provide concrete implementations (e.g., Symfony EventDispatcher).
 */
interface EventDispatcherPort
{
    /**
     * Dispatches a domain event to all registered listeners.
     */
    public function dispatch(object $event): void;
}
