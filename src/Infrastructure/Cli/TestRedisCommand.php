<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli;

use Exception;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\RedisConnectionService;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\RedisTestResultFormatter;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\RedisTestRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(
    name: 'redis:test',
    description: 'Test Redis connection',
)]
final readonly class TestRedisCommand
{
    public function __construct(
        private RedisConnectionService $connectionService,
        private RedisTestRunner $testRunner,
        private RedisTestResultFormatter $formatter,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $io->title('Testing Redis Connection');
        $io->success('Redis PHP extension is loaded');

        $io->info(sprintf('MESSENGER_TRANSPORT_DSN: %s', $this->connectionService->getDsn()));

        try {
            $config = $this->connectionService->parseDsn();
            $this->formatter->displayConnectionInfo($io, $config);

            $redis = $this->connectionService->connect();
            $this->formatter->displayConnectionSuccess($io);

            $testResult = $this->testRunner->runReadWriteTest($redis);
            $this->formatter->displayReadWriteTest($io, $testResult);

            $queues = $this->testRunner->getMessengerQueues($redis);
            $this->formatter->displayMessengerQueues($io, $queues);

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $io->error(sprintf('Redis error: %s', $exception->getMessage()));

            return Command::FAILURE;
        }
    }
}
