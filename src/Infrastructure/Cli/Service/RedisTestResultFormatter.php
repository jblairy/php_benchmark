<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

/**
 * Formats and displays Redis test results.
 */
final readonly class RedisTestResultFormatter
{
    /**
     * @param array{success: bool, key: string, value: string, retrieved: string} $testResult
     */
    public function displayReadWriteTest(SymfonyStyle $io, array $testResult): void
    {
        $io->section('Testing Redis operations');
        $io->writeln(sprintf('SET %s = "%s"', $testResult['key'], $testResult['value']));
        $io->writeln(sprintf('GET %s = "%s"', $testResult['key'], $testResult['retrieved']));

        if ($testResult['success']) {
            $io->success('Redis read/write test passed!');
        } else {
            $io->error('Redis read/write test failed!');
        }
    }

    /**
     * @param array<string, int> $queues
     */
    public function displayMessengerQueues(SymfonyStyle $io, array $queues): void
    {
        $io->section('Checking Messenger queues');

        if ([] === $queues) {
            $io->info('No Messenger queues found in Redis');

            return;
        }

        foreach ($queues as $queueName => $count) {
            $io->writeln(sprintf('%s: %d messages', $queueName, $count));
        }
    }

    /**
     * @param array{host: string, port: int, database: int} $config
     */
    public function displayConnectionInfo(SymfonyStyle $io, array $config): void
    {
        $io->info(sprintf('Connecting to Redis at %s:%d', $config['host'], $config['port']));
        if (0 !== $config['database']) {
            $io->info(sprintf('Using database: %d', $config['database']));
        }
    }

    public function displayConnectionSuccess(SymfonyStyle $io): void
    {
        $io->success('Successfully connected to Redis!');
    }
}
