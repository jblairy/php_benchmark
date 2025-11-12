<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function date;
use function number_format;
use function sprintf;

/**
 * Service for rendering dashboard output to console.
 */
final readonly class DashboardRenderer
{
    /**
     * Render dashboard header with timestamp.
     */
    public function renderHeader(SymfonyStyle $symfonyStyle): void
    {
        $symfonyStyle->title('Messenger Status Dashboard');
        $symfonyStyle->writeln(sprintf('<comment>%s</comment>', date('Y-m-d H:i:s')));
        $symfonyStyle->newLine();
    }

    /**
     * Render queue statistics table.
     *
     * @param array{async: int, failed: int, delayed: int} $stats
     */
    public function renderQueueStats(
        SymfonyStyle $symfonyStyle,
        OutputInterface $output,
        array $stats,
    ): void {
        $symfonyStyle->section('Message Queues');

        $table = new Table($output);
        $table->setHeaders(['Queue', 'Status', 'Count']);

        $table->addRow([
            'async',
            0 < $stats['async'] ? '<fg=yellow>Processing</>' : '<fg=green>Empty</>',
            (string) $stats['async'],
        ]);

        $table->addRow([
            'failed',
            0 < $stats['failed'] ? '<fg=red>Has Failed</>' : '<fg=green>Empty</>',
            (string) $stats['failed'],
        ]);

        $table->addRow([
            'delayed',
            0 < $stats['delayed'] ? '<fg=yellow>Scheduled</>' : '<fg=green>Empty</>',
            (string) $stats['delayed'],
        ]);

        $table->render();
    }

    /**
     * Render queue status messages.
     */
    public function renderQueueStatusMessages(
        SymfonyStyle $symfonyStyle,
        int $totalPending,
        int $failedCount,
    ): void {
        if (0 >= $totalPending) {
            $symfonyStyle->success('All queues are empty');
        }

        if (0 < $totalPending) {
            $symfonyStyle->warning(sprintf('%d messages pending processing', $totalPending));
        }

        if (0 < $failedCount) {
            $symfonyStyle->error(sprintf('%d failed messages! Run "messenger:failed:retry" to retry them.', $failedCount));
        }
    }

    /**
     * Render Redis performance statistics.
     *
     * @param array{instantaneous_ops_per_sec: int, total_commands_processed: int, connected_clients: int, used_memory_human: string} $redisStats
     */
    public function renderRedisStats(SymfonyStyle $symfonyStyle, array $redisStats): void
    {
        $symfonyStyle->section('Redis Performance');

        $symfonyStyle->writeln([
            sprintf('Operations/sec: <comment>%d</comment>', $redisStats['instantaneous_ops_per_sec']),
            sprintf('Total operations: <comment>%s</comment>', number_format($redisStats['total_commands_processed'])),
            sprintf('Connected clients: <comment>%d</comment>', $redisStats['connected_clients']),
            sprintf('Memory usage: <comment>%s</comment>', $redisStats['used_memory_human']),
        ]);
    }

    /**
     * Render worker status information.
     */
    public function renderWorkerStatus(SymfonyStyle $symfonyStyle, int $activeWorkers): void
    {
        $symfonyStyle->section('Worker Status');

        if (0 >= $activeWorkers) {
            $symfonyStyle->info('Worker status detection via heartbeat not implemented.');
            $symfonyStyle->note('Workers are managed by supervisor/docker-compose');

            return;
        }

        $symfonyStyle->success(sprintf('%d active workers detected', $activeWorkers));
    }

    /**
     * Clear screen for watch mode.
     */
    public function clearScreen(OutputInterface $output): void
    {
        $output->write("\033[2J\033[H");
    }
}
