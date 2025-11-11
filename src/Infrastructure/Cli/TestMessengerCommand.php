<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli;

use Exception;
use Jblairy\PhpBenchmark\Application\Message\ExecuteBenchmarkMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

#[AsCommand(
    name: 'messenger:test',
    description: 'Test Messenger configuration by sending a test message',
)]
final class TestMessengerCommand
{
    public function __construct(private readonly MessageBusInterface $messageBus)
    {
    }

    public function __invoke(SymfonyStyle $symfonyStyle): int
    {
        $symfonyStyle->title('Testing Messenger Configuration');

        try {
            // Create a test message
            $executeBenchmarkMessage = new ExecuteBenchmarkMessage(
                benchmarkClass: 'Test\\Benchmark\\Class',
                benchmarkSlug: 'test-benchmark',
                benchmarkName: 'Test Benchmark',
                phpVersion: 'php84',
                iterations: 1,
                executionId: 'test_' . uniqid(),
                iterationNumber: 1,
            );

            $symfonyStyle->section('Dispatching test message...');
            $symfonyStyle->writeln('Message details:');
            $symfonyStyle->listing([
                'Class: ' . $executeBenchmarkMessage->benchmarkClass,
                'Name: ' . $executeBenchmarkMessage->benchmarkName,
                'Execution ID: ' . $executeBenchmarkMessage->executionId,
            ]);

            // Dispatch the message
            $envelope = $this->messageBus->dispatch($executeBenchmarkMessage);

            // Check which transport received the message
            $transportStamp = $envelope->last(TransportNamesStamp::class);
            if ($transportStamp instanceof TransportNamesStamp) {
                $transports = $transportStamp->getTransportNames();
                $symfonyStyle->success(sprintf('Message dispatched to transport(s): %s', implode(', ', $transports)));
            } else {
                $symfonyStyle->warning('Message dispatched but transport information not available.');
            }

            $symfonyStyle->section('Next steps:');
            $symfonyStyle->listing([
                'Run "php bin/console messenger:consume async -vv" to process the message',
                'Check worker logs in var/log/messenger-worker-*.log',
                'Use "php bin/console messenger:failed:show" to see failed messages',
            ]);

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $symfonyStyle->error(sprintf('Failed to dispatch message: %s', $exception->getMessage()));
            $symfonyStyle->writeln('Stack trace:');
            $symfonyStyle->writeln($exception->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
