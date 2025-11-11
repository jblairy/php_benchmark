<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Logging;

use Jblairy\PhpBenchmark\Domain\Benchmark\Port\LoggerPort;
use Psr\Log\LoggerInterface;

/**
 * Adapter that bridges Domain LoggerPort to PSR-3 LoggerInterface.
 *
 * This allows the Domain to remain independent of PSR-3 while still
 * using standard logging implementations (Monolog, etc.).
 */
final readonly class PsrLoggerAdapter implements LoggerPort
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }
}
