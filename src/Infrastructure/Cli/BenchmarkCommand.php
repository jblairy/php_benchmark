<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli;

use Exception;
use InvalidArgumentException;
use Jblairy\PhpBenchmark\Application\UseCase\BenchmarkOrchestrator;
use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'benchmark:run',
    description: 'Execute PHP benchmarks across different versions',
)]
final readonly class BenchmarkCommand
{
    public function __construct(
        private BenchmarkOrchestrator $benchmarkOrchestrator,
        private BenchmarkRepositoryPort $benchmarkRepositoryPort,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        InputInterface $input,
        #[Option]
        ?string $test = null,
        #[Option]
        int $iterations = 0,
        #[Option]
        ?string $php_version = null,
    ): int {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $testName = $test;
        $iterations = (int) $iterations;
        $phpVersionName = $php_version;

        if (0 >= $iterations) {
            $symfonyStyle->error('Iterations must be greater than 0');

            return Command::FAILURE;
        }

        try {
            if (null !== $testName && null !== $phpVersionName) {
                $this->executeSingleBenchmark($symfonyStyle, $testName, $phpVersionName, $iterations);
            } elseif (null !== $testName) {
                $this->executeBenchmarkAllVersions($symfonyStyle, $testName, $iterations);
            } else {
                $this->executeAllBenchmarks($symfonyStyle, $iterations);
            }

            $symfonyStyle->success('Benchmark(s) completed successfully!');

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $symfonyStyle->error(sprintf('Benchmark failed: %s', $exception->getMessage()));

            return Command::FAILURE;
        }
    }

    private function executeSingleBenchmark(
        SymfonyStyle $symfonyStyle,
        string $testName,
        string $phpVersionName,
        int $iterations,
    ): void {
        $benchmark = $this->findBenchmark($testName);
        $phpVersion = PhpVersion::from($phpVersionName);

        $symfonyStyle->title(sprintf(
            'Running %s on %s (%d iterations)',
            $testName,
            $phpVersion->value,
            $iterations,
        ));

        $benchmarkConfiguration = new BenchmarkConfiguration(
            benchmark: $benchmark,
            phpVersion: $phpVersion,
            iterations: $iterations,
        );

        $this->benchmarkOrchestrator->executeSingle($benchmarkConfiguration);
    }

    private function executeBenchmarkAllVersions(
        SymfonyStyle $symfonyStyle,
        string $testName,
        int $iterations,
    ): void {
        $benchmark = $this->findBenchmark($testName);

        $symfonyStyle->title(sprintf(
            'Running %s across all PHP versions (%d iterations)',
            $testName,
            $iterations,
        ));

        $this->benchmarkOrchestrator->executeMultiple(
            benchmarks: [$benchmark],
            phpVersions: PhpVersion::cases(),
            iterations: $iterations,
        );
    }

    private function executeAllBenchmarks(SymfonyStyle $symfonyStyle, int $iterations): void
    {
        $symfonyStyle->title(sprintf(
            'Running all benchmarks across all PHP versions (%d iterations)',
            $iterations,
        ));

        $this->benchmarkOrchestrator->executeAll($this->benchmarkRepositoryPort, $iterations);
    }

    private function findBenchmark(string $name): Benchmark
    {
        $benchmark = $this->benchmarkRepositoryPort->findBenchmarkByName($name);

        if (!$benchmark instanceof Benchmark) {
            throw new InvalidArgumentException(sprintf('Benchmark "%s" not found', $name));
        }

        return $benchmark;
    }
}
