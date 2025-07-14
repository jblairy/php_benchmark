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
        private BenchmarkRunner $benchmarkRunner,
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

        if (!$this->configurator->isNotConfiguratedForSingleRun()) {
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
            $this->configurator->setBenchmark($test);
        }

        $this->configurator->setIterations($iterations);

        if (null !== $phpVersion) {
            $this->configurator->setPhpVersion(PhpVersion::from($phpVersion));
        }

        $this->benchmarkRunner->configure($this->configurator);
    }

    private function createAndRunMultipleProcess(): void
    {
        if (file_exists('benchmark.csv')) {
            unlink('benchmark.csv');
        }

        foreach ($this->generateProcesses() as $process) {
            while ($process->isRunning()) {
                // TODO log
            }

            if (!$process->isSuccessful()) {
                echo 'Error : ' . $process->getErrorOutput();
            }
        }
    }

    /**
     * @return iterable<Process>
     */
    private function generateProcesses(): iterable
    {
        foreach ($this->configurator->getAllBenchmarks() as $benchmark) {
            foreach ($this->configurator->getAllPhpVersions() as $phpVersion) {
                $benchmarkName = explode('/', $benchmark::class);
                $process = new Process(['php', 'bin/console', 'benchmark:run', '--test=' . end($benchmarkName), '--php-version=' . $phpVersion->value, '--iterations=' . $this->configurator->getIterations()]);
                $process->start();

                yield $process;
            }
        }
    }

    private function executeBenchmarkRunnerAndAppendToCsv(): void
    {
        $this->benchmarkRunner->execute();

        file_put_contents(
            'benchmark.csv',
            $this->serializer->serialize($this->benchmarkRunner->getResults(), CsvEncoder::FORMAT),
            flags: FILE_APPEND,
        );
    }
}
