<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli;

use Exception;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\QueueStatsService;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\RecentMessagesService;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\RedisConnectionService;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\RedisInfoService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(
    name: 'messenger:queue-status',
    description: 'Show the status of Messenger queues (Redis transport)',
)]
final readonly class MessengerStatusCommand
{
    public function __construct(
        private RedisConnectionService $connectionService,
        private QueueStatsService $queueStatsService,
        private RecentMessagesService $recentMessagesService,
        private RedisInfoService $redisInfoService,
    ) {
    }

    public function __invoke(OutputInterface $output, SymfonyStyle $io): int
    {
        $io->title('Messenger Queue Status (Redis)');

        try {
            $this->connectionService->connect();
            $config = $this->connectionService->parseDsn();
            $io->success(sprintf('Connected to Redis at %s:%d', $config['host'], $config['port']));

            if (0 !== $config['database']) {
                $io->info(sprintf('Selected Redis database: %d', $config['database']));
            }

            $this->displayTransportConfiguration($io);
            $this->displayQueueStats($io);
            $this->displayRecentMessages($io, $output);
            $this->displayRedisInfo($io);

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $io->error(sprintf('Failed to get queue status: %s', $exception->getMessage()));

            return Command::FAILURE;
        }
    }

    private function displayTransportConfiguration(SymfonyStyle $io): void
    {
        $io->section('Transport Configuration');
        $io->writeln(sprintf('Current transport: <comment>%s</comment>', $this->connectionService->getDsn()));
    }

    private function displayQueueStats(SymfonyStyle $io): void
    {
        $io->section('Queue Statistics');

        try {
            $redis = $this->connectionService->connect();
            $stats = $this->queueStatsService->getQueueStats($redis);

            if ([] === $stats) {
                $io->info('No message queues found in Redis.');
                $io->note('Messages are stored with keys like "messages:queue_name"');

                return;
            }

            $table = new Table($io);
            $table->setHeaders(['Queue', 'Pending Messages']);

            foreach ($stats as $queueName => $count) {
                $table->addRow([$queueName, (string) $count]);
            }

            $table->render();

            $total = $this->queueStatsService->getTotalMessages($stats);
            $io->writeln(sprintf('Total pending messages: <comment>%d</comment>', $total));
        } catch (Exception $exception) {
            $io->error(sprintf('Failed to get queue stats: %s', $exception->getMessage()));
        }
    }

    private function displayRecentMessages(SymfonyStyle $io, OutputInterface $output): void
    {
        $io->section('Recent Messages');

        try {
            $redis = $this->connectionService->connect();
            $messages = $this->recentMessagesService->getRecentMessages($redis);

            if ([] === $messages) {
                $io->info('No messages to show.');

                return;
            }

            $table = new Table($output);
            $table->setHeaders(['Queue', 'Message Preview (first 100 chars)', 'Position']);

            foreach ($messages as $message) {
                $table->addRow([
                    $message['queue'],
                    $message['preview'],
                    (string) $message['position'],
                ]);
            }

            $table->render();

            $io->note('Showing up to 5 messages per queue (without removing them)');
        } catch (Exception $exception) {
            $io->error(sprintf('Failed to get recent messages: %s', $exception->getMessage()));
        }
    }

    private function displayRedisInfo(SymfonyStyle $io): void
    {
        $io->section('Redis Server Info');

        try {
            $redis = $this->connectionService->connect();
            $info = $this->redisInfoService->getServerInfo($redis);

            $io->writeln('Redis Version: <comment>' . $info['version'] . '</comment>');
            $io->writeln('Connected Clients: <comment>' . $info['clients'] . '</comment>');
            $io->writeln('Used Memory: <comment>' . $info['memory'] . '</comment>');
            $io->writeln('Total Commands Processed: <comment>' . $info['commands'] . '</comment>');

            if (0 < $info['failed_count']) {
                $io->warning(sprintf('Failed messages queue contains %d messages!', $info['failed_count']));
                $io->note('Use "php bin/console messenger:failed:retry" to retry failed messages');
            }
        } catch (Exception $exception) {
            $io->error(sprintf('Failed to get Redis info: %s', $exception->getMessage()));
        }
    }
}
