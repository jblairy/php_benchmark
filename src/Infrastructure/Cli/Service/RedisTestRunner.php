<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Redis;

use function is_array;
use function is_int;
use function is_string;
use function uniqid;

/**
 * Executes Redis connection tests and operations.
 */
final readonly class RedisTestRunner
{
    /**
     * @return array{success: bool, key: string, value: string, retrieved: string}
     */
    public function runReadWriteTest(Redis $redis): array
    {
        $testKey = 'test:' . uniqid();
        $testValue = 'Hello from PHP Benchmark!';

        $redis->set($testKey, $testValue);
        $retrieved = $redis->get($testKey);
        $retrievedString = is_string($retrieved) ? $retrieved : '';

        $redis->del($testKey);

        return [
            'success' => $retrievedString === $testValue,
            'key' => $testKey,
            'value' => $testValue,
            'retrieved' => $retrievedString,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function getMessengerQueues(Redis $redis): array
    {
        $messageKeysResult = $redis->keys('messages:*');
        $messageKeys = is_array($messageKeysResult) ? $messageKeysResult : [];

        $queues = [];
        foreach ($messageKeys as $messageKey) {
            if (!is_string($messageKey)) {
                continue;
            }

            $countResult = $redis->lLen($messageKey);
            $count = is_int($countResult) ? $countResult : 0;
            $queues[$messageKey] = $count;
        }

        return $queues;
    }
}
