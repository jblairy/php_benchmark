<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Redis;

use function is_array;
use function is_int;
use function is_string;
use function str_replace;

/**
 * Retrieves queue statistics from Redis.
 */
final readonly class QueueStatsService
{
    /**
     * @return array<string, int>
     */
    public function getQueueStats(Redis $redis): array
    {
        $keysResult = $redis->keys('messages:*');
        $keys = is_array($keysResult) ? $keysResult : [];

        $stats = [];
        foreach ($keys as $key) {
            if (!is_string($key)) {
                continue;
            }

            $queueName = str_replace('messages:', '', $key);
            $countResult = $redis->lLen($key);
            $count = is_int($countResult) ? $countResult : 0;
            $stats[$queueName] = $count;
        }

        return $stats;
    }

    /**
     * @param array<string, int> $stats
     */
    public function getTotalMessages(array $stats): int
    {
        $total = 0;
        foreach ($stats as $count) {
            $total += $count;
        }

        return $total;
    }
}
