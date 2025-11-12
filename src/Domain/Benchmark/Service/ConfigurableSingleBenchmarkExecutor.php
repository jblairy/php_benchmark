<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Service;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\ExecutionContext;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\IterationConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkExecutorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\CodeExtractorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptBuilderPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptExecutorPort;

/**
 * Enhanced benchmark executor that uses per-benchmark iteration configuration.
 */
final readonly class ConfigurableSingleBenchmarkExecutor implements BenchmarkExecutorPort
{
    public function __construct(
        private CodeExtractorPort $codeExtractorPort,
        private ScriptBuilderPort $scriptBuilderPort,
        private ScriptExecutorPort $scriptExecutorPort,
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
            $iterationConfig = IterationConfiguration::createWithDefaults(
                warmupIterations: $warmupIterations,
                innerIterations: $innerIterations,
                benchmarkCode: $code,
            );

            return $this->scriptBuilderPort->buildWithIterationConfig($code, $iterationConfig);
        }

        return $this->scriptBuilderPort->build($code);
    }
}
