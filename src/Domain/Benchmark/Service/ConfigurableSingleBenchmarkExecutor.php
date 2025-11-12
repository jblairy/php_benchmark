<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Service;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\ExecutionContext;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkExecutorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\CodeExtractorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptBuilderPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptExecutorPort;
use Jblairy\PhpBenchmark\Infrastructure\Benchmark\Factory\IterationConfigurationFactory;

/**
 * Enhanced benchmark executor that uses per-benchmark iteration configuration.
 */
final readonly class ConfigurableSingleBenchmarkExecutor implements BenchmarkExecutorPort
{
    public function __construct(
        private CodeExtractorPort $codeExtractorPort,
        private ScriptBuilderPort $scriptBuilderPort,
        private ScriptExecutorPort $scriptExecutorPort,
        private IterationConfigurationFactory $iterConfigFactory,
    ) {
    }

    public function execute(BenchmarkConfiguration $benchmarkConfiguration): BenchmarkResult
    {
        $code = $this->codeExtractorPort->extractCode(
            $benchmarkConfiguration->benchmark,
            $benchmarkConfiguration->phpVersion,
        );

        $benchmark = $benchmarkConfiguration->benchmark;
        $warmupIterations = $benchmark->getWarmupIterations();
        $innerIterations = $benchmark->getInnerIterations();

        $script = $this->buildScriptWithIterations($code, $warmupIterations, $innerIterations);

        $slug = $benchmarkConfiguration->benchmark->getSlug();

        $executionContext = new ExecutionContext(
            phpVersion: $benchmarkConfiguration->phpVersion,
            scriptContent: $script,
            benchmarkClassName: $benchmarkConfiguration->benchmark::class,
            benchmarkSlug: $slug,
        );

        return $this->scriptExecutorPort->executeScript($executionContext);
    }

    private function buildScriptWithIterations(
        string $code,
        ?int $warmupIterations,
        ?int $innerIterations,
    ): string {
        if (null !== $warmupIterations && null !== $innerIterations) {
            $iterationConfig = $this->iterConfigFactory->createFromExplicitValues(
                warmupIterations: $warmupIterations,
                innerIterations: $innerIterations,
            );

            return $this->scriptBuilderPort->buildWithIterationConfig($code, $iterationConfig);
        }

        return $this->scriptBuilderPort->build($code);
    }
}
