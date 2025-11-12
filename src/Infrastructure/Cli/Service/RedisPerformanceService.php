<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Redis;

use function is_array;
use function is_numeric;
use function is_string;

/**
 * Service for retrieving Redis performance metrics.
 */
final readonly class RedisPerformanceService
{
    /**
     * Get Redis performance statistics.
     *
     * @return array{instantaneous_ops_per_sec: int, total_commands_processed: int, connected_clients: int, used_memory_human: string}
     */
    public function getPerformanceStats(Redis $redis): array
    {
        $info = $redis->info();
        $stats = $redis->info('stats');

        $infoArray = is_array($info) ? $info : [];
        $statsArray = is_array($stats) ? $stats : [];

        return [
            'instantaneous_ops_per_sec' => $this->extractInstantaneousOps($statsArray),
            'total_commands_processed' => $this->extractTotalCommands($infoArray),
            'connected_clients' => $this->extractConnectedClients($infoArray),
            'used_memory_human' => $this->extractMemoryUsage($infoArray),
        ];
    }

    /**
     * @param array<string, mixed> $stats
     */
    private function extractInstantaneousOps(array $stats): int
    {
        return isset($stats['instantaneous_ops_per_sec']) && is_numeric($stats['instantaneous_ops_per_sec'])
            ? (int) $stats['instantaneous_ops_per_sec']
            : 0;
    }

    /**
     * @param array<string, mixed> $info
     */
    private function extractTotalCommands(array $info): int
    {
        return isset($info['total_commands_processed']) && is_numeric($info['total_commands_processed'])
            ? (int) $info['total_commands_processed']
            : 0;
    }

    /**
     * @param array<string, mixed> $info
     */
    private function extractConnectedClients(array $info): int
    {
        return isset($info['connected_clients']) && is_numeric($info['connected_clients'])
            ? (int) $info['connected_clients']
            : 0;
    }

    /**
     * @param array<string, mixed> $info
     */
    private function extractMemoryUsage(array $info): string
    {
        return isset($info['used_memory_human']) && is_string($info['used_memory_human'])
            ? $info['used_memory_human']
            : 'Unknown';
    }
}
