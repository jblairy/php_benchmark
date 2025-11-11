<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli;

use Exception;
use Redis;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'messenger:monitor',
    description: 'Monitor Messenger workers and queue status in real-time',
)]
final class MessengerMonitorCommand extends Command
{
    private ?Redis $redis = null;

    protected function configure(): void
    {
        $this
            ->addOption('interval', 'i', InputOption::VALUE_REQUIRED, 'Refresh interval in seconds', '2')
            ->addOption('watch', 'w', InputOption::VALUE_NONE, 'Watch mode (continuous monitoring)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $interval = (int) $input->getOption('interval');
        $watch = $input->getOption('watch');

        if (!$this->connectToRedis($io)) {
            return Command::FAILURE;
        }

        if ($watch) {
            $io->title('Messenger Monitor - Real-time Dashboard');
            $io->note('Press Ctrl+C to stop monitoring');

            while (true) {
                // Clear screen
                $output->write("\033[2J\033[H");

                $this->showDashboard($io, $output);

                sleep($interval);
            }
        } else {
            $this->showDashboard($io, $output);
        }

        return Command::SUCCESS;
    }

    private function showDashboard(SymfonyStyle $io, OutputInterface $output): void
    {
        $io->title('Messenger Status Dashboard');
        $io->writeln(sprintf('<comment>%s</comment>', date('Y-m-d H:i:s')));
        $io->newLine();

        // Queue Statistics
        $this->showQueueStats($io, $output);
        $io->newLine();

        // Redis Stats
        $this->showRedisStats($io);
        $io->newLine();

        // Worker Status (from container health checks)
        $this->showWorkerStatus($io);
    }

    private function connectToRedis(SymfonyStyle $io): bool
    {
        try {
            $dsn = $_ENV['MESSENGER_TRANSPORT_DSN'] ?? '';
            $parsedDsn = parse_url($dsn);
            $host = $parsedDsn['host'] ?? 'localhost';
            $port = $parsedDsn['port'] ?? 6379;

            $this->redis = new Redis();

            return $this->redis->connect($host, (int) $port);
        } catch (Exception $e) {
            $io->error(sprintf('Redis connection failed: %s', $e->getMessage()));

            return false;
        }
    }

    private function showQueueStats(SymfonyStyle $io, OutputInterface $output): void
    {
        $io->section('Message Queues');

        // Check for messages in different states
        $asyncQueue = $this->redis->lLen('messages:async') ?? 0;
        $failedQueue = $this->redis->lLen('messages:failed') ?? 0;
        $delayedQueue = $this->redis->zCard('messages:delayed:async') ?? 0;

        $table = new Table($output);
        $table->setHeaders(['Queue', 'Status', 'Count']);

        $table->addRow([
            'async',
            0 < $asyncQueue ? '<fg=yellow>Processing</>' : '<fg=green>Empty</>',
            $asyncQueue,
        ]);

        $table->addRow([
            'failed',
            0 < $failedQueue ? '<fg=red>Has Failed</>' : '<fg=green>Empty</>',
            $failedQueue,
        ]);

        $table->addRow([
            'delayed',
            0 < $delayedQueue ? '<fg=yellow>Scheduled</>' : '<fg=green>Empty</>',
            $delayedQueue,
        ]);

        $table->render();

        $totalPending = $asyncQueue + $delayedQueue;
        if (0 < $totalPending) {
            $io->warning(sprintf('%d messages pending processing', $totalPending));
        } else {
            $io->success('All queues are empty');
        }

        if (0 < $failedQueue) {
            $io->error(sprintf('%d failed messages! Run "messenger:failed:retry" to retry them.', $failedQueue));
        }
    }

    private function showRedisStats(SymfonyStyle $io): void
    {
        $io->section('Redis Performance');

        $info = $this->redis->info();
        $stats = $this->redis->info('stats');

        $instantOps = $stats['instantaneous_ops_per_sec'] ?? 0;
        $totalOps = $info['total_commands_processed'] ?? 0;
        $connectedClients = $info['connected_clients'] ?? 0;
        $usedMemory = $info['used_memory_human'] ?? 'Unknown';

        $io->writeln([
            sprintf('Operations/sec: <comment>%d</comment>', $instantOps),
            sprintf('Total operations: <comment>%s</comment>', number_format((int) $totalOps)),
            sprintf('Connected clients: <comment>%d</comment>', $connectedClients),
            sprintf('Memory usage: <comment>%s</comment>', $usedMemory),
        ]);
    }

    private function showWorkerStatus(SymfonyStyle $io): void
    {
        $io->section('Worker Status');

        // In a real implementation, you'd check worker health via supervisor API
        // or by checking process status. For now, we'll check Redis connections
        // as a proxy for worker activity

        $workerKeys = $this->redis->keys('worker:*:heartbeat');
        $activeWorkers = count($workerKeys);

        if (0 < $activeWorkers) {
            $io->success(sprintf('%d active workers detected', $activeWorkers));
        } else {
            $io->info('Worker status detection via heartbeat not implemented.');
            $io->note('Workers are managed by supervisor/docker-compose');
        }
    }
}
