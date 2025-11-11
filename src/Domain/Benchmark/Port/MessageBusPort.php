<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

/**
 * Port for dispatching messages to an asynchronous message bus.
 *
 * This abstraction allows the Domain to be independent of any specific
 * message bus implementation (Symfony Messenger, RabbitMQ, etc.).
 */
interface MessageBusPort
{
    /**
     * Dispatches a message to the message bus for asynchronous processing.
     *
     * @param object $message The message to dispatch
     */
    public function dispatch(object $message): void;
}
