<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

/**
 * Port for logging messages in the Domain layer.
 *
 * This abstraction allows the Domain to be independent of any specific
 * logging implementation (PSR-3, custom logger, etc.).
 */
interface LoggerPort
{
    /**
     * Detailed debug information.
     *
     * @param array<string, mixed> $context
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Interesting events.
     *
     * @param array<string, mixed> $context
     */
    public function info(string $message, array $context = []): void;

    /**
     * Normal but significant events.
     *
     * @param array<string, mixed> $context
     */
    public function notice(string $message, array $context = []): void;

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param array<string, mixed> $context
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Runtime errors that do not require immediate action.
     *
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context = []): void;

    /**
     * Critical conditions.
     *
     * @param array<string, mixed> $context
     */
    public function critical(string $message, array $context = []): void;
}
