<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Service;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\ExecutionContext;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\IterationConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkExecutorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\CodeExtractorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptBuilderPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptExecutorPort;
use Jblairy\PhpBenchmark\Infrastructure\Execution\ScriptBuilding\ConfigurableScriptBuilder;

/**
 * Enhanced benchmark executor that uses per-benchmark iteration configuration.
 */
final readonly class ConfigurableSingleBenchmarkExecutor implements BenchmarkExecutorPort
{
    public function __construct(
        private CodeExtractorPort $codeExtractorPort,
        private ScriptBuilderPort $scriptBuilderPort,
        private ScriptExecutorPort $scriptExecutorPort,
        private BenchmarkRepositoryPort $benchmarkRepository,
    ) {
    }

    public function execute(BenchmarkConfiguration $benchmarkConfiguration): BenchmarkResult
    {
        $code = $this->codeExtractorPort->extractCode(
            $benchmarkConfiguration->benchmark,
            $benchmarkConfiguration->phpVersion,
        );

        // Try to get benchmark entity for custom iterations
        $benchmark = $this->benchmarkRepository->findBenchmarkBySlug(
            $benchmarkConfiguration->benchmark->getSlug(),
        );

        // Configure the script builder if it supports configuration
        if ($this->scriptBuilderPort instanceof ConfigurableScriptBuilder && null !== $benchmark) {
            // Access the underlying entity through DatabaseBenchmark adapter
            $benchmarkEntity = $benchmark instanceof \Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Adapter\DatabaseBenchmark
                ? $benchmark->getEntity()
                : null;

            if (null !== $benchmarkEntity) {
                $iterationConfig = IterationConfiguration::createWithDefaults(
                    $benchmarkEntity->getWarmupIterations(),
                    $benchmarkEntity->getInnerIterations(),
                    $code,
                );

                $this->scriptBuilderPort->setIterationConfiguration($iterationConfig);
            }
        }

        $script = $this->scriptBuilderPort->build($code);

        $slug = $benchmarkConfiguration->benchmark->getSlug();

        $executionContext = new ExecutionContext(
            phpVersion: $benchmarkConfiguration->phpVersion,
            scriptContent: $script,
            benchmarkClassName: $benchmarkConfiguration->benchmark::class,
            benchmarkSlug: $slug,
        );

        return $this->scriptExecutorPort->executeScript($executionContext);
    }
}
