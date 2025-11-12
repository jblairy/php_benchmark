<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Redis;

use function is_array;
use function is_int;
use function is_string;
use function mb_strlen;
use function mb_substr;
use function str_replace;

/**
 * Retrieves recent messages from Redis queues.
 */
final readonly class RecentMessagesService
{
    /**
     * @return array<int, array{queue: string, preview: string, position: int}>
     */
    public function getRecentMessages(Redis $redis, int $limit = 5, int $previewLength = 100): array
    {
        $keysResult = $redis->keys('messages:*');
        $keys = is_array($keysResult) ? $keysResult : [];

        $messages = [];
        foreach ($keys as $key) {
            if (!is_string($key)) {
                continue;
            }

            $queueName = str_replace('messages:', '', $key);
            $messagesResult = $redis->lrange($key, 0, $limit - 1);
            $queueMessages = is_array($messagesResult) ? $messagesResult : [];

            foreach ($queueMessages as $index => $message) {
                if (!is_string($message)) {
                    continue;
                }

                $preview = mb_substr($message, 0, $previewLength);
                if ($previewLength < mb_strlen($message)) {
                    $preview .= '...';
                }

                $position = is_int($index) ? $index + 1 : 1;

                $messages[] = [
                    'queue' => $queueName,
                    'preview' => $preview,
                    'position' => $position,
                ];
            }
        }

        return $messages;
    }
}
