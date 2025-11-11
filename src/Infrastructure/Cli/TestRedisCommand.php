<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'redis:test',
    description: 'Test Redis connection',
)]
final class TestRedisCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Testing Redis Connection');
        
        // Check if Redis extension is loaded
        if (!extension_loaded('redis')) {
            $io->error('Redis PHP extension is not installed!');
            $io->note('Install it with: pecl install redis');
            return Command::FAILURE;
        }
        
        $io->success('Redis PHP extension is loaded');
        
        // Get DSN
        $dsn = $_ENV['MESSENGER_TRANSPORT_DSN'] ?? '';
        $io->info(sprintf('MESSENGER_TRANSPORT_DSN: %s', $dsn));
        
        if (!str_starts_with($dsn, 'redis://')) {
            $io->error('MESSENGER_TRANSPORT_DSN must start with redis://');
            return Command::FAILURE;
        }
        
        // Parse DSN
        $parsedDsn = parse_url($dsn);
        $host = $parsedDsn['host'] ?? 'localhost';
        $port = $parsedDsn['port'] ?? 6379;
        
        $io->info(sprintf('Connecting to Redis at %s:%d', $host, $port));
        
        try {
            $redis = new \Redis();
            $connected = $redis->connect($host, (int)$port, 2.0); // 2 second timeout
            
            if (!$connected) {
                $io->error('Could not connect to Redis!');
                return Command::FAILURE;
            }
            
            $io->success('Successfully connected to Redis!');
            
            // Test operations
            $testKey = 'test:' . uniqid();
            $testValue = 'Hello from PHP Benchmark!';
            
            $io->section('Testing Redis operations');
            
            // SET
            $redis->set($testKey, $testValue);
            $io->writeln(sprintf('SET %s = "%s"', $testKey, $testValue));
            
            // GET
            $retrieved = $redis->get($testKey);
            $io->writeln(sprintf('GET %s = "%s"', $testKey, $retrieved));
            
            if ($retrieved === $testValue) {
                $io->success('Redis read/write test passed!');
            } else {
                $io->error('Redis read/write test failed!');
            }
            
            // Cleanup
            $redis->del($testKey);
            
            // Check Messenger queues
            $io->section('Checking Messenger queues');
            $messageKeys = $redis->keys('messages:*');
            
            if (empty($messageKeys)) {
                $io->info('No Messenger queues found in Redis');
            } else {
                foreach ($messageKeys as $key) {
                    $count = $redis->lLen($key);
                    $io->writeln(sprintf('%s: %d messages', $key, $count));
                }
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error(sprintf('Redis error: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}