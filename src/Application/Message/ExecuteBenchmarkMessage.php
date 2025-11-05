<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Message;

/**
 * Message for asynchronous benchmark execution via Symfony Messenger.
 *
 * This message is dispatched to the async transport and processed by ExecuteBenchmarkHandler.
 * It contains all data needed to execute a single benchmark iteration.
 */
final readonly class ExecuteBenchmarkMessage
{
    public function __construct(
        public string $benchmarkClass,
        public string $benchmarkSlug,
        public string $benchmarkName,
        public string $phpVersion,
        public int $iterations,
        public string $executionId,
        public int $iterationNumber,
    ) {
    }
}
