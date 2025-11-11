<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli;

use Exception;
use Redis;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'messenger:queue-status',
    description: 'Show the status of Messenger queues (Redis transport)',
)]
final class MessengerStatusCommand extends Command
{
    private ?Redis $redis = null;

    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Messenger Queue Status (Redis)');

        try {
            // Connect to Redis
            if (!$this->connectToRedis($io)) {
                return Command::FAILURE;
            }

            // Get transport configuration
            $io->section('Transport Configuration');
            $transportDsn = $_ENV['MESSENGER_TRANSPORT_DSN'] ?? 'Not configured';
            $io->writeln(sprintf('Current transport: <comment>%s</comment>', $transportDsn));

            // Get queue statistics
            $io->section('Queue Statistics');
            $this->showQueueStats($io);

            // Get recent messages
            $io->section('Recent Messages');
            $this->showRecentMessages($io, $output);

            // Show Redis info
            $io->section('Redis Server Info');
            $this->showRedisInfo($io);

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error(sprintf('Failed to get queue status: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }

    private function connectToRedis(SymfonyStyle $io): bool
    {
        try {
            // Parse Redis DSN
            $dsn = $_ENV['MESSENGER_TRANSPORT_DSN'] ?? '';
            if (!str_starts_with($dsn, 'redis://')) {
                $io->error('MESSENGER_TRANSPORT_DSN must be a Redis DSN (redis://...)');

                return false;
            }

            $parsedDsn = parse_url($dsn);
            $host = $parsedDsn['host'] ?? 'localhost';
            $port = $parsedDsn['port'] ?? 6379;
            $path = mb_trim($parsedDsn['path'] ?? '', '/');

            // Check if Redis extension is loaded
            if (!extension_loaded('redis')) {
                $io->error('Redis PHP extension is not installed.');
                $io->note('Install it with: pecl install redis');

                return false;
            }

            $this->redis = new Redis();
            $connected = $this->redis->connect($host, (int) $port);

            if (!$connected) {
                $io->error(sprintf('Could not connect to Redis at %s:%d', $host, $port));

                return false;
            }

            $io->success(sprintf('Connected to Redis at %s:%d', $host, $port));

            // Select database if specified in path
            if ($path && is_numeric($path)) {
                $this->redis->select((int) $path);
                $io->info(sprintf('Selected Redis database: %d', (int) $path));
            }

            return true;
        } catch (Exception $e) {
            $io->error(sprintf('Redis connection failed: %s', $e->getMessage()));

            return false;
        }
    }

    private function showQueueStats(SymfonyStyle $io): void
    {
        // Redis stores messages in lists
        // Symfony Messenger uses keys like: messages:queue_name
        $keys = $this->redis->keys('messages:*');

        if (empty($keys)) {
            $io->info('No message queues found in Redis.');
            $io->note('Messages are stored with keys like "messages:queue_name"');

            return;
        }

        $table = new Table($io);
        $table->setHeaders(['Queue', 'Pending Messages']);

        $totalMessages = 0;
        foreach ($keys as $key) {
            $queueName = str_replace('messages:', '', $key);
            $count = $this->redis->lLen($key);
            $table->addRow([$queueName, $count]);
            $totalMessages += $count;
        }

        $table->render();

        $io->writeln(sprintf('Total pending messages: <comment>%d</comment>', $totalMessages));
    }

    private function showRecentMessages(SymfonyStyle $io, OutputInterface $output): void
    {
        // Get all queue keys
        $keys = $this->redis->keys('messages:*');

        if (empty($keys)) {
            $io->info('No messages to show.');

            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Queue', 'Message Preview (first 100 chars)', 'Position']);

        foreach ($keys as $key) {
            $queueName = str_replace('messages:', '', $key);

            // Get first 5 messages from each queue without removing them
            $messages = $this->redis->lRange($key, 0, 4);

            foreach ($messages as $index => $message) {
                $preview = mb_substr($message, 0, 100);
                if (100 < mb_strlen($message)) {
                    $preview .= '...';
                }

                $table->addRow([
                    $queueName,
                    $preview,
                    $index + 1,
                ]);
            }
        }

        $table->render();

        $io->note('Showing up to 5 messages per queue (without removing them)');
    }

    private function showRedisInfo(SymfonyStyle $io): void
    {
        $info = $this->redis->info();

        $io->writeln('Redis Version: <comment>' . ($info['redis_version'] ?? 'Unknown') . '</comment>');
        $io->writeln('Connected Clients: <comment>' . ($info['connected_clients'] ?? 'Unknown') . '</comment>');
        $io->writeln('Used Memory: <comment>' . ($info['used_memory_human'] ?? 'Unknown') . '</comment>');
        $io->writeln('Total Commands Processed: <comment>' . ($info['total_commands_processed'] ?? 'Unknown') . '</comment>');

        // Check for failed queue
        $failedCount = $this->redis->lLen('messages:failed');
        if (0 < $failedCount) {
            $io->warning(sprintf('Failed messages queue contains %d messages!', $failedCount));
            $io->note('Use "php bin/console messenger:failed:retry" to retry failed messages');
        }
    }
}
