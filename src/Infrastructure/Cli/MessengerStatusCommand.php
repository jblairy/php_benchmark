<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli;

use Exception;
use Redis;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function extension_loaded;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function mb_strlen;
use function mb_substr;
use function mb_trim;
use function parse_url;
use function sprintf;
use function str_replace;
use function str_starts_with;

#[AsCommand(
    name: 'messenger:queue-status',
    description: 'Show the status of Messenger queues (Redis transport)',
)]
final class MessengerStatusCommand
{
    private ?Redis $redis = null;

    public function __construct()
    {
    }

    public function __invoke(OutputInterface $output, SymfonyStyle $symfonyStyle): int
    {
        $symfonyStyle->title('Messenger Queue Status (Redis)');

        try {
            // Connect to Redis
            if (!$this->connectToRedis($symfonyStyle)) {
                return Command::FAILURE;
            }

            // Get transport configuration
            $symfonyStyle->section('Transport Configuration');
            $transportDsn = $_ENV['MESSENGER_TRANSPORT_DSN'] ?? 'Not configured';
            $displayDsn = is_string($transportDsn) ? $transportDsn : 'Not configured';
            $symfonyStyle->writeln(sprintf('Current transport: <comment>%s</comment>', $displayDsn));

            // Get queue statistics
            $symfonyStyle->section('Queue Statistics');
            $this->showQueueStats($symfonyStyle);

            // Get recent messages
            $symfonyStyle->section('Recent Messages');
            $this->showRecentMessages($symfonyStyle, $output);

            // Show Redis info
            $symfonyStyle->section('Redis Server Info');
            $this->showRedisInfo($symfonyStyle);

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $symfonyStyle->error(sprintf('Failed to get queue status: %s', $exception->getMessage()));

            return Command::FAILURE;
        }
    }

    private function connectToRedis(SymfonyStyle $symfonyStyle): bool
    {
        try {
            // Parse Redis DSN
            $dsn = $_ENV['MESSENGER_TRANSPORT_DSN'] ?? '';

            if (!is_string($dsn) || !str_starts_with($dsn, 'redis://')) {
                $symfonyStyle->error('MESSENGER_TRANSPORT_DSN must be a Redis DSN (redis://...)');

                return false;
            }

            $parsedDsn = parse_url($dsn);
            if (false === $parsedDsn) {
                $symfonyStyle->error('Invalid MESSENGER_TRANSPORT_DSN format');

                return false;
            }

            $host = is_string($parsedDsn['host'] ?? null) ? $parsedDsn['host'] : 'localhost';
            $port = is_int($parsedDsn['port'] ?? null) ? $parsedDsn['port'] : 6379;
            $pathRaw = $parsedDsn['path'] ?? '';
            $path = is_string($pathRaw) ? mb_trim($pathRaw, '/') : '';

            // Check if Redis extension is loaded
            if (!extension_loaded('redis')) {
                $symfonyStyle->error('Redis PHP extension is not installed.');
                $symfonyStyle->note('Install it with: pecl install redis');

                return false;
            }

            $this->redis = new Redis();
            $connected = $this->redis->connect($host, $port);

            if (!$connected) {
                $symfonyStyle->error(sprintf('Could not connect to Redis at %s:%d', $host, $port));

                return false;
            }

            $symfonyStyle->success(sprintf('Connected to Redis at %s:%d', $host, $port));

            // Select database if specified in path
            if ('' !== $path && is_numeric($path)) {
                $this->redis->select((int) $path);
                $symfonyStyle->info(sprintf('Selected Redis database: %d', (int) $path));
            }

            return true;
        } catch (Exception $exception) {
            $symfonyStyle->error(sprintf('Redis connection failed: %s', $exception->getMessage()));

            return false;
        }
    }

    private function showQueueStats(SymfonyStyle $symfonyStyle): void
    {
        if (!$this->redis instanceof Redis) {
            $symfonyStyle->error('Redis connection is not established');

            return;
        }

        // Redis stores messages in lists
        // Symfony Messenger uses keys like: messages:queue_name
        $keysResult = $this->redis->keys('messages:*');
        $keys = is_array($keysResult) ? $keysResult : [];

        if ([] === $keys) {
            $symfonyStyle->info('No message queues found in Redis.');
            $symfonyStyle->note('Messages are stored with keys like "messages:queue_name"');

            return;
        }

        $table = new Table($symfonyStyle);
        $table->setHeaders(['Queue', 'Pending Messages']);

        $totalMessages = 0;
        foreach ($keys as $key) {
            if (!is_string($key)) {
                continue;
            }

            $queueName = str_replace('messages:', '', $key);
            $countResult = $this->redis->lLen($key);
            $count = is_int($countResult) ? $countResult : 0;
            $table->addRow([$queueName, (string) $count]);
            $totalMessages += $count;
        }

        $table->render();

        $symfonyStyle->writeln(sprintf('Total pending messages: <comment>%d</comment>', $totalMessages));
    }

    private function showRecentMessages(SymfonyStyle $symfonyStyle, OutputInterface $output): void
    {
        if (!$this->redis instanceof Redis) {
            $symfonyStyle->error('Redis connection is not established');

            return;
        }

        // Get all queue keys
        $keysResult = $this->redis->keys('messages:*');
        $keys = is_array($keysResult) ? $keysResult : [];

        if ([] === $keys) {
            $symfonyStyle->info('No messages to show.');

            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Queue', 'Message Preview (first 100 chars)', 'Position']);

        foreach ($keys as $key) {
            if (!is_string($key)) {
                continue;
            }

            $queueName = str_replace('messages:', '', $key);

            // Get first 5 messages from each queue without removing them
            $messagesResult = $this->redis->lrange($key, 0, 4);
            $messages = is_array($messagesResult) ? $messagesResult : [];

            foreach ($messages as $index => $message) {
                if (!is_string($message)) {
                    continue;
                }

                $preview = mb_substr($message, 0, 100);
                if (100 < mb_strlen($message)) {
                    $preview .= '...';
                }

                $position = is_int($index) ? $index + 1 : 1;

                $table->addRow([
                    $queueName,
                    $preview,
                    (string) $position,
                ]);
            }
        }

        $table->render();

        $symfonyStyle->note('Showing up to 5 messages per queue (without removing them)');
    }

    private function showRedisInfo(SymfonyStyle $symfonyStyle): void
    {
        if (!$this->redis instanceof Redis) {
            $symfonyStyle->error('Redis connection is not established');

            return;
        }

        $infoResult = $this->redis->info();
        $info = is_array($infoResult) ? $infoResult : [];

        $redisVersion = isset($info['redis_version']) && is_string($info['redis_version'])
            ? $info['redis_version']
            : 'Unknown';

        $connectedClients = isset($info['connected_clients']) && is_numeric($info['connected_clients'])
            ? (string) $info['connected_clients']
            : 'Unknown';

        $usedMemory = isset($info['used_memory_human']) && is_string($info['used_memory_human'])
            ? $info['used_memory_human']
            : 'Unknown';

        $totalCommands = isset($info['total_commands_processed']) && is_numeric($info['total_commands_processed'])
            ? (string) $info['total_commands_processed']
            : 'Unknown';

        $symfonyStyle->writeln('Redis Version: <comment>' . $redisVersion . '</comment>');
        $symfonyStyle->writeln('Connected Clients: <comment>' . $connectedClients . '</comment>');
        $symfonyStyle->writeln('Used Memory: <comment>' . $usedMemory . '</comment>');
        $symfonyStyle->writeln('Total Commands Processed: <comment>' . $totalCommands . '</comment>');

        // Check for failed queue
        $failedCountResult = $this->redis->lLen('messages:failed');
        $failedCount = is_int($failedCountResult) ? $failedCountResult : 0;

        if (0 < $failedCount) {
            $symfonyStyle->warning(sprintf('Failed messages queue contains %d messages!', $failedCount));
            $symfonyStyle->note('Use "php bin/console messenger:failed:retry" to retry failed messages');
        }
    }
}
