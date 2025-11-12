<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Redis;

use function is_array;
use function is_int;
use function is_numeric;
use function is_string;

/**
 * Retrieves Redis server information.
 */
final readonly class RedisInfoService
{
    /**
     * @return array{version: string, clients: string, memory: string, commands: string, failed_count: int}
     */
    public function getServerInfo(Redis $redis): array
    {
        $infoResult = $redis->info();
        $info = is_array($infoResult) ? $infoResult : [];

        return [
            'version' => $this->extractVersion($info),
            'clients' => $this->extractClients($info),
            'memory' => $this->extractMemory($info),
            'commands' => $this->extractCommands($info),
            'failed_count' => $this->extractFailedCount($redis),
        ];
    }

    /**
     * @param array<string, mixed> $info
     */
    private function extractVersion(array $info): string
    {
        return isset($info['redis_version']) && is_string($info['redis_version'])
            ? $info['redis_version']
            : 'Unknown';
    }

    /**
     * @param array<string, mixed> $info
     */
    private function extractClients(array $info): string
    {
        return isset($info['connected_clients']) && is_numeric($info['connected_clients'])
            ? (string) $info['connected_clients']
            : 'Unknown';
    }

    /**
     * @param array<string, mixed> $info
     */
    private function extractMemory(array $info): string
    {
        return isset($info['used_memory_human']) && is_string($info['used_memory_human'])
            ? $info['used_memory_human']
            : 'Unknown';
    }

    /**
     * @param array<string, mixed> $info
     */
    private function extractCommands(array $info): string
    {
        return isset($info['total_commands_processed']) && is_numeric($info['total_commands_processed'])
            ? (string) $info['total_commands_processed']
            : 'Unknown';
    }

    private function extractFailedCount(Redis $redis): int
    {
        $failedCountResult = $redis->lLen('messages:failed');

        return is_int($failedCountResult) ? $failedCountResult : 0;
    }
}
