<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Command;

use Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark\BenchmarkRunner;
use Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark\Configurator\Configurator;
use Jblairy\PhpBenchmark\PhpVersion\Enum\PhpVersion;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(name: 'benchmark:run', description: 'Execute a benchmark')]
final class BenchmarkCommand extends Command
{
    public function __construct(
        private Configurator $configurator,
        private SerializerInterface $serializer,
    ) {
        parent::__construct();
    }

    public function __invoke(
        InputInterface $input,
        SymfonyStyle $io,
    ): int {
        $testName = $input->getOption('test');
        if (is_string($testName) && '' !== $testName) {
            $this->configurator->setBenchmarkName($testName);
        }

        $iterations = $input->getOption('iterations');
        if (is_string($iterations) && '' !== $iterations) {
            $this->configurator->setIterations($iterations);
        }

        $phpVersion = $input->getOption('php-version');
        if (is_string($phpVersion) && '' !== $phpVersion) {
            $this->configurator->setPhpVersion(PhpVersion::from($phpVersion));
        }

        $runner = new BenchmarkRunner($this->configurator);
        $runner->execute();

        file_put_contents(
            'benchmark.csv',
            $this->serializer->serialize($runner->getResults(), CsvEncoder::FORMAT),
        );

        $io->success('Benchmark finished.');

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->addOption('test', null, InputOption::VALUE_REQUIRED, 'Name of the test to run', '')
            ->addOption('iterations', null, InputOption::VALUE_OPTIONAL, 'Number of iterations to run', '')
            ->addOption('php-version', null, InputOption::VALUE_OPTIONAL, 'Php version to run', '');
    }
}
