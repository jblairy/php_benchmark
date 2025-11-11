<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli;

use Exception;
use Jblairy\PhpBenchmark\Application\Message\ExecuteBenchmarkMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

#[AsCommand(
    name: 'messenger:test',
    description: 'Test Messenger configuration by sending a test message',
)]
final class TestMessengerCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Testing Messenger Configuration');

        try {
            // Create a test message
            $testMessage = new ExecuteBenchmarkMessage(
                benchmarkClass: 'Test\\Benchmark\\Class',
                benchmarkSlug: 'test-benchmark',
                benchmarkName: 'Test Benchmark',
                phpVersion: 'php84',
                iterations: 1,
                executionId: 'test_' . uniqid(),
                iterationNumber: 1,
            );

            $io->section('Dispatching test message...');
            $io->writeln('Message details:');
            $io->listing([
                'Class: ' . $testMessage->benchmarkClass,
                'Name: ' . $testMessage->benchmarkName,
                'Execution ID: ' . $testMessage->executionId,
            ]);

            // Dispatch the message
            $envelope = $this->messageBus->dispatch($testMessage);

            // Check which transport received the message
            $transportStamp = $envelope->last(TransportNamesStamp::class);
            if ($transportStamp instanceof TransportNamesStamp) {
                $transports = $transportStamp->getTransportNames();
                $io->success(sprintf('Message dispatched to transport(s): %s', implode(', ', $transports)));
            } else {
                $io->warning('Message dispatched but transport information not available.');
            }

            $io->section('Next steps:');
            $io->listing([
                'Run "php bin/console messenger:consume async -vv" to process the message',
                'Check worker logs in var/log/messenger-worker-*.log',
                'Use "php bin/console messenger:failed:show" to see failed messages',
            ]);

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error(sprintf('Failed to dispatch message: %s', $e->getMessage()));
            $io->writeln('Stack trace:');
            $io->writeln($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
