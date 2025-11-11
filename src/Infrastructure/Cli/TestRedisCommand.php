<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli;

use Exception;
use Redis;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function extension_loaded;
use function is_array;
use function is_int;
use function is_string;
use function parse_url;
use function sprintf;
use function str_starts_with;
use function uniqid;

#[AsCommand(
    name: 'redis:test',
    description: 'Test Redis connection',
)]
final class TestRedisCommand
{
    public function __invoke(SymfonyStyle $symfonyStyle): int
    {
        $symfonyStyle->title('Testing Redis Connection');
        // Check if Redis extension is loaded
        if (!extension_loaded('redis')) {
            $symfonyStyle->error('Redis PHP extension is not installed!');
            $symfonyStyle->note('Install it with: pecl install redis');

            return Command::FAILURE;
        }
        $symfonyStyle->success('Redis PHP extension is loaded');
        // Get DSN
        $dsn = $_ENV['MESSENGER_TRANSPORT_DSN'] ?? '';
        $dsnString = is_string($dsn) ? $dsn : '';
        $symfonyStyle->info(sprintf('MESSENGER_TRANSPORT_DSN: %s', $dsnString));
        if (!str_starts_with($dsnString, 'redis://')) {
            $symfonyStyle->error('MESSENGER_TRANSPORT_DSN must start with redis://');

            return Command::FAILURE;
        }
        // Parse DSN
        $parsedDsn = parse_url($dsnString);
        if (false === $parsedDsn) {
            $symfonyStyle->error('Invalid DSN format');

            return Command::FAILURE;
        }
        $host = is_string($parsedDsn['host'] ?? null) ? $parsedDsn['host'] : 'localhost';
        $port = is_int($parsedDsn['port'] ?? null) ? $parsedDsn['port'] : 6379;
        $symfonyStyle->info(sprintf('Connecting to Redis at %s:%d', $host, $port));

        try {
            $redis = new Redis();
            $connected = $redis->connect($host, $port, 2.0); // 2 second timeout

            if (!$connected) {
                $symfonyStyle->error('Could not connect to Redis!');

                return Command::FAILURE;
            }

            $symfonyStyle->success('Successfully connected to Redis!');

            // Test operations
            $testKey = 'test:' . uniqid();
            $testValue = 'Hello from PHP Benchmark!';

            $symfonyStyle->section('Testing Redis operations');

            // SET
            $redis->set($testKey, $testValue);
            $symfonyStyle->writeln(sprintf('SET %s = "%s"', $testKey, $testValue));

            // GET
            $retrieved = $redis->get($testKey);
            $retrievedString = is_string($retrieved) ? $retrieved : '';
            $symfonyStyle->writeln(sprintf('GET %s = "%s"', $testKey, $retrievedString));

            if ($retrievedString === $testValue) {
                $symfonyStyle->success('Redis read/write test passed!');
            } else {
                $symfonyStyle->error('Redis read/write test failed!');
            }

            // Cleanup
            $redis->del($testKey);

            // Check Messenger queues
            $symfonyStyle->section('Checking Messenger queues');
            $messageKeysResult = $redis->keys('messages:*');
            $messageKeys = is_array($messageKeysResult) ? $messageKeysResult : [];

            if ([] === $messageKeys) {
                $symfonyStyle->info('No Messenger queues found in Redis');
            } else {
                foreach ($messageKeys as $messageKey) {
                    if (!is_string($messageKey)) {
                        continue;
                    }

                    $countResult = $redis->lLen($messageKey);
                    $count = is_int($countResult) ? $countResult : 0;
                    $symfonyStyle->writeln(sprintf('%s: %d messages', $messageKey, $count));
                }
            }

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $symfonyStyle->error(sprintf('Redis error: %s', $exception->getMessage()));

            return Command::FAILURE;
        }
    }
}
