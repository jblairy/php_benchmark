<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli;

use Exception;
use Redis;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function count;
use function date;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function number_format;
use function parse_url;
use function sleep;
use function sprintf;

#[AsCommand(
    name: 'messenger:monitor',
    description: 'Monitor Messenger workers and queue status in real-time',
)]
final class MessengerMonitorCommand
{
    private ?Redis $redis = null;

    public function __invoke(#[\Symfony\Component\Console\Attribute\Option(name: 'interval', shortcut: 'i', description: 'Refresh interval in seconds')]
        string $interval = '2', #[\Symfony\Component\Console\Attribute\Option(name: 'watch', shortcut: 'w', description: 'Watch mode (continuous monitoring)')]
        bool $watch = false, ?OutputInterface $output = null, ?SymfonyStyle $symfonyStyle = null): int
    {
        if (null === $symfonyStyle || null === $output) {
            return Command::FAILURE;
        }

        $intervalOption = $interval;
        $interval = is_numeric($intervalOption) ? (int) $intervalOption : 2;

        if (!$this->connectToRedis($symfonyStyle)) {
            return Command::FAILURE;
        }

        if ($watch) {
            $symfonyStyle->title('Messenger Monitor - Real-time Dashboard');
            $symfonyStyle->note('Press Ctrl+C to stop monitoring');

            // Infinite loop for watch mode - user must press Ctrl+C to stop
            // @phpstan-ignore-next-line while.alwaysTrue - intentional infinite loop
            while (true) {
                // Clear screen
                $output->write("\033[2J\033[H");

                $this->showDashboard($symfonyStyle, $output);

                sleep($interval);
            }
        } else {
            $this->showDashboard($symfonyStyle, $output);
        }

        return Command::SUCCESS;
    }

    private function showDashboard(SymfonyStyle $symfonyStyle, OutputInterface $output): void
    {
        $symfonyStyle->title('Messenger Status Dashboard');
        $symfonyStyle->writeln(sprintf('<comment>%s</comment>', date('Y-m-d H:i:s')));
        $symfonyStyle->newLine();

        // Queue Statistics
        $this->showQueueStats($symfonyStyle, $output);
        $symfonyStyle->newLine();

        // Redis Stats
        $this->showRedisStats($symfonyStyle);
        $symfonyStyle->newLine();

        // Worker Status (from container health checks)
        $this->showWorkerStatus($symfonyStyle);
    }

    private function connectToRedis(SymfonyStyle $symfonyStyle): bool
    {
        try {
            $dsn = $_ENV['MESSENGER_TRANSPORT_DSN'] ?? '';

            if (!is_string($dsn) || '' === $dsn) {
                $symfonyStyle->error('MESSENGER_TRANSPORT_DSN is not configured');

                return false;
            }

            $parsedDsn = parse_url($dsn);
            if (false === $parsedDsn) {
                $symfonyStyle->error('Invalid MESSENGER_TRANSPORT_DSN format');

                return false;
            }

            $host = is_string($parsedDsn['host'] ?? null) ? $parsedDsn['host'] : 'localhost';
            $port = is_int($parsedDsn['port'] ?? null) ? $parsedDsn['port'] : 6379;

            $this->redis = new Redis();

            return $this->redis->connect($host, $port);
        } catch (Exception $exception) {
            $symfonyStyle->error(sprintf('Redis connection failed: %s', $exception->getMessage()));

            return false;
        }
    }

    private function showQueueStats(SymfonyStyle $symfonyStyle, OutputInterface $output): void
    {
        if (!$this->redis instanceof Redis) {
            $symfonyStyle->error('Redis connection is not established');

            return;
        }

        $symfonyStyle->section('Message Queues');

        // Check for messages in different states
        $asyncQueueResult = $this->redis->lLen('messages:async');
        $asyncQueue = is_int($asyncQueueResult) ? $asyncQueueResult : 0;

        $failedQueueResult = $this->redis->lLen('messages:failed');
        $failedQueue = is_int($failedQueueResult) ? $failedQueueResult : 0;

        $delayedQueueResult = $this->redis->zCard('messages:delayed:async');
        $delayedQueue = is_int($delayedQueueResult) ? $delayedQueueResult : 0;

        $table = new Table($output);
        $table->setHeaders(['Queue', 'Status', 'Count']);

        $table->addRow([
            'async',
            0 < $asyncQueue ? '<fg=yellow>Processing</>' : '<fg=green>Empty</>',
            (string) $asyncQueue,
        ]);

        $table->addRow([
            'failed',
            0 < $failedQueue ? '<fg=red>Has Failed</>' : '<fg=green>Empty</>',
            (string) $failedQueue,
        ]);

        $table->addRow([
            'delayed',
            0 < $delayedQueue ? '<fg=yellow>Scheduled</>' : '<fg=green>Empty</>',
            (string) $delayedQueue,
        ]);

        $table->render();

        $totalPending = $asyncQueue + $delayedQueue;
        if (0 < $totalPending) {
            $symfonyStyle->warning(sprintf('%d messages pending processing', $totalPending));
        } else {
            $symfonyStyle->success('All queues are empty');
        }

        if (0 < $failedQueue) {
            $symfonyStyle->error(sprintf('%d failed messages! Run "messenger:failed:retry" to retry them.', $failedQueue));
        }
    }

    private function showRedisStats(SymfonyStyle $symfonyStyle): void
    {
        if (!$this->redis instanceof Redis) {
            $symfonyStyle->error('Redis connection is not established');

            return;
        }

        $symfonyStyle->section('Redis Performance');

        $info = $this->redis->info();
        $stats = $this->redis->info('stats');

        $infoArray = is_array($info) ? $info : [];
        $statsArray = is_array($stats) ? $stats : [];

        $instantOps = isset($statsArray['instantaneous_ops_per_sec']) && is_numeric($statsArray['instantaneous_ops_per_sec'])
            ? (int) $statsArray['instantaneous_ops_per_sec']
            : 0;

        $totalOps = isset($infoArray['total_commands_processed']) && is_numeric($infoArray['total_commands_processed'])
            ? (int) $infoArray['total_commands_processed']
            : 0;

        $connectedClients = isset($infoArray['connected_clients']) && is_numeric($infoArray['connected_clients'])
            ? (int) $infoArray['connected_clients']
            : 0;

        $usedMemory = isset($infoArray['used_memory_human']) && is_string($infoArray['used_memory_human'])
            ? $infoArray['used_memory_human']
            : 'Unknown';

        $symfonyStyle->writeln([
            sprintf('Operations/sec: <comment>%d</comment>', $instantOps),
            sprintf('Total operations: <comment>%s</comment>', number_format($totalOps)),
            sprintf('Connected clients: <comment>%d</comment>', $connectedClients),
            sprintf('Memory usage: <comment>%s</comment>', $usedMemory),
        ]);
    }

    private function showWorkerStatus(SymfonyStyle $symfonyStyle): void
    {
        if (!$this->redis instanceof Redis) {
            $symfonyStyle->error('Redis connection is not established');

            return;
        }

        $symfonyStyle->section('Worker Status');

        // In a real implementation, you'd check worker health via supervisor API
        // or by checking process status. For now, we'll check Redis connections
        // as a proxy for worker activity

        $workerKeysResult = $this->redis->keys('worker:*:heartbeat');
        $workerKeys = is_array($workerKeysResult) ? $workerKeysResult : [];
        $activeWorkers = count($workerKeys);

        if (0 < $activeWorkers) {
            $symfonyStyle->success(sprintf('%d active workers detected', $activeWorkers));
        } else {
            $symfonyStyle->info('Worker status detection via heartbeat not implemented.');
            $symfonyStyle->note('Workers are managed by supervisor/docker-compose');
        }
    }
}
