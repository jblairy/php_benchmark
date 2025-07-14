<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Command;

use Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark\BenchmarkRunner;
use Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark\Configurator\Configurator;
use Jblairy\PhpBenchmark\PhpVersion\Enum\PhpVersion;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(name: 'benchmark:run', description: 'Execute a benchmark')]
final class BenchmarkCommand
{
    public function __construct(
        private Configurator $configurator,
        private SerializerInterface $serializer,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Name of  the test to run', name: 'test')]
        ?string $test = null,
        #[Option(description: 'Number of iterations to run', name: 'iterations')]
        int $iterations = 1,
        #[Option(description: 'Php version to run', name: 'php-version')]
        ?string $phpVersion = null,
    ): int {
        $this->configure($test, $iterations, $phpVersion);

        if (1 < count($this->configurator->getPhpVersion()) || 1 < count($this->configurator->getBenchmarks())) {
            $io->title('🚀 PHP Benchmark Runner');
            $this->createAndRunMultipleProcess();
            $io->success('Benchmark finished.');

            return Command::SUCCESS;
        }

        $this->executeBenchmarkRunnerAndAppendToCsv();

        return Command::SUCCESS;
    }

    private function configure(?string $test, int $iterations, ?string $phpVersion): void
    {
        if (null !== $test) {
            $this->configurator->setBenchmarkName($test);
        }

        $this->configurator->setIterations($iterations);

        if (null !== $phpVersion) {
            $this->configurator->setPhpVersion(PhpVersion::from($phpVersion));
        }
    }

    private function createAndRunMultipleProcess(): void
    {
        $processes = [];
        if (file_exists('benchmark.csv')) {
            unlink('benchmark.csv');
        }
        if (file_exists('debug.log')) {
            unlink('debug.log');
        }

        foreach ($this->configurator->getBenchmarks() as $benchmark) {
            foreach ($this->configurator->getPhpVersion() as $phpVersion) {
                $benchmarkName = explode('/', $benchmark::class);
                $process = new Process(['php', 'bin/console', 'benchmark:run', '--test=' . end($benchmarkName), '--php-version=' . $phpVersion->value, '--iterations=' . $this->configurator->getIterations()]);
                $process->start();
                $processes[] = $process;
            }
        }

        foreach ($processes as $process) {
            while ($process->isRunning()) {
                // TODO log
            }

            if (!$process->isSuccessful()) {
                echo 'Error : ' . $process->getErrorOutput();
            }
        }
    }

    private function executeBenchmarkRunnerAndAppendToCsv(): void
    {
        $runner = new BenchmarkRunner($this->configurator);
        $runner->execute();

        file_put_contents(
            'benchmark.csv',
            $this->serializer->serialize($runner->getResults(), CsvEncoder::FORMAT),
            flags: FILE_APPEND,
        );
    }
}
