<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\UseCase;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\CodeExtractorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Exception\ReflexionMethodNotFound;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;

final class BenchmarkOrchestrator
{
    public function __construct(
        private readonly AsyncBenchmarkRunner $runner,
        private readonly CodeExtractorPort $codeExtractor,
    ) {
    }

    public function executeSingle(BenchmarkConfiguration $configuration): void
    {
        $this->runner->run($configuration);
    }

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

                $this->runner->run($configuration);
            }
        }
    }

    private function benchmarkSupportsVersion(Benchmark $benchmark, PhpVersion $phpVersion): bool
    {
        try {
            $this->codeExtractor->extractCode($benchmark, $phpVersion);
            return true;
        } catch (ReflexionMethodNotFound) {
            return false;
        }
    }

    public function executeAll(BenchmarkRepositoryPort $registry, int $iterations): void
    {
        $this->executeMultiple(
            benchmarks: $registry->getAllBenchmarks(),
            phpVersions: PhpVersion::cases(),
            iterations: $iterations
        );
    }
}
