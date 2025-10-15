<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli;

use Jblairy\PhpBenchmark\Application\UseCase\BenchmarkOrchestrator;
use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'benchmark:run',
    description: 'Execute PHP benchmarks across different versions'
)]
final class BenchmarkCommand extends Command
{
    public function __construct(
        private readonly BenchmarkOrchestrator $orchestrator,
        private readonly BenchmarkRepositoryPort $registry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'test',
                't',
                InputOption::VALUE_OPTIONAL,
                'Name of specific benchmark to run'
            )
            ->addOption(
                'iterations',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Number of iterations to run',
                1
            )
            ->addOption(
                'php-version',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Specific PHP version to test (e.g., php84, php85)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $testName = $input->getOption('test');
        $iterations = (int) $input->getOption('iterations');
        $phpVersionName = $input->getOption('php-version');

        if ($iterations <= 0) {
            $io->error('Iterations must be greater than 0');

            return Command::FAILURE;
        }

        try {
            if ($testName !== null && $phpVersionName !== null) {
                $this->executeSingleBenchmark($io, $testName, $phpVersionName, $iterations);
            } elseif ($testName !== null) {
                $this->executeBenchmarkAllVersions($io, $testName, $iterations);
            } else {
                $this->executeAllBenchmarks($io, $iterations);
            }

            $io->success('Benchmark(s) completed successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Benchmark failed: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }

    private function executeSingleBenchmark(
        SymfonyStyle $io,
        string $testName,
        string $phpVersionName,
        int $iterations
    ): void {
        $benchmark = $this->findBenchmark($testName);
        $phpVersion = PhpVersion::from($phpVersionName);

        $io->title(sprintf(
            'Running %s on %s (%d iterations)',
            $testName,
            $phpVersion->value,
            $iterations
        ));

        $configuration = new BenchmarkConfiguration(
            benchmark: $benchmark,
            phpVersion: $phpVersion,
            iterations: $iterations
        );

        $this->orchestrator->executeSingle($configuration);
    }

    private function executeBenchmarkAllVersions(
        SymfonyStyle $io,
        string $testName,
        int $iterations
    ): void {
        $benchmark = $this->findBenchmark($testName);

        $io->title(sprintf(
            'Running %s across all PHP versions (%d iterations)',
            $testName,
            $iterations
        ));

        $this->orchestrator->executeMultiple(
            benchmarks: [$benchmark],
            phpVersions: PhpVersion::cases(),
            iterations: $iterations
        );
    }

    private function executeAllBenchmarks(SymfonyStyle $io, int $iterations): void
    {
        $io->title(sprintf(
            'Running all benchmarks across all PHP versions (%d iterations)',
            $iterations
        ));

        $this->orchestrator->executeAll($this->registry, $iterations);
    }

    private function findBenchmark(string $name): Benchmark
    {
        $benchmark = $this->registry->findBenchmarkByName($name);

        if ($benchmark === null) {
            throw new \InvalidArgumentException(
                sprintf('Benchmark "%s" not found', $name)
            );
        }

        return $benchmark;
    }
}
