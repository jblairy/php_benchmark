<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Redis;

/**
 * Service for monitoring message queue statistics.
 */
final readonly class QueueMonitorService
{
    /**
     * Get queue statistics from Redis.
     *
     * @return array{async: int, failed: int, delayed: int}
     */
    public function getQueueStats(Redis $redis): array
    {
        $asyncQueueResult = $redis->lLen('messages:async');
        $asyncQueue = is_int($asyncQueueResult) ? $asyncQueueResult : 0;

        $failedQueueResult = $redis->lLen('messages:failed');
        $failedQueue = is_int($failedQueueResult) ? $failedQueueResult : 0;

        $delayedQueueResult = $redis->zCard('messages:delayed:async');
        $delayedQueue = is_int($delayedQueueResult) ? $delayedQueueResult : 0;

        return [
            'async' => $asyncQueue,
            'failed' => $failedQueue,
            'delayed' => $delayedQueue,
        ];
    }

    /**
     * Calculate total pending messages.
     *
     * @param array{async: int, failed: int, delayed: int} $stats
     */
    public function getTotalPending(array $stats): int
    {
        return $stats['async'] + $stats['delayed'];
    }
}
