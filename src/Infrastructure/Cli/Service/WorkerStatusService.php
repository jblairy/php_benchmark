<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Redis;

use function count;
use function is_array;

/**
 * Service for checking worker status and health.
 */
final readonly class WorkerStatusService
{
    /**
     * Get number of active workers from Redis heartbeat keys.
     */
    public function getActiveWorkerCount(Redis $redis): int
    {
        $workerKeysResult = $redis->keys('worker:*:heartbeat');
        $workerKeys = is_array($workerKeysResult) ? $workerKeysResult : [];

        return count($workerKeys);
    }

    /**
     * Check if workers are detected.
     */
    public function hasActiveWorkers(Redis $redis): bool
    {
        return 0 < $this->getActiveWorkerCount($redis);
    }
}
