<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\UseCase;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Exception\ReflexionMethodNotFound;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\CodeExtractorPort;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;

final readonly class BenchmarkOrchestrator
{
    public function __construct(
        private MessengerBenchmarkRunner $messengerBenchmarkRunner,
        private CodeExtractorPort $codeExtractorPort,
    ) {
    }

    public function executeSingle(BenchmarkConfiguration $benchmarkConfiguration): void
    {
        $this->messengerBenchmarkRunner->run($benchmarkConfiguration);
    }

    /**
     * @param Benchmark[]  $benchmarks
     * @param PhpVersion[] $phpVersions
     */
    public function executeMultiple(array $benchmarks, array $phpVersions, int $iterations): void
    {
        foreach ($benchmarks as $benchmark) {
            foreach ($phpVersions as $phpVersion) {
                if (!$this->benchmarkSupportsVersion($benchmark, $phpVersion)) {
                    continue;
                }

                $configuration = new BenchmarkConfiguration(
                    benchmark: $benchmark,
                    phpVersion: $phpVersion,
                    iterations: $iterations,
                );

                $this->messengerBenchmarkRunner->run($configuration);
            }
        }
    }

    public function executeAll(BenchmarkRepositoryPort $benchmarkRepositoryPort, int $iterations): void
    {
        $this->executeMultiple(
            benchmarks: $benchmarkRepositoryPort->getAllBenchmarks(),
            phpVersions: PhpVersion::cases(),
            iterations: $iterations,
        );
    }

    private function benchmarkSupportsVersion(Benchmark $benchmark, PhpVersion $phpVersion): bool
    {
        try {
            $this->codeExtractorPort->extractCode($benchmark, $phpVersion);

            return true;
        } catch (ReflexionMethodNotFound) {
            return false;
        }
    }
}
