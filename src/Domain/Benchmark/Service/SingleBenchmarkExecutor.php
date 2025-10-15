<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Service;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\ExecutionContext;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkExecutorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\CodeExtractorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptExecutorPort;
use Jblairy\PhpBenchmark\Infrastructure\Execution\ScriptBuilding\InstrumentedScriptBuilder;

final class SingleBenchmarkExecutor implements BenchmarkExecutorPort
{
    public function __construct(
        private readonly CodeExtractorPort $codeExtractor,
        private readonly InstrumentedScriptBuilder $scriptBuilder,
        private readonly ScriptExecutorPort $scriptExecutor,
    ) {
    }

    public function execute(BenchmarkConfiguration $configuration): BenchmarkResult
    {
        $code = $this->codeExtractor->extractCode(
            $configuration->benchmark,
            $configuration->phpVersion
        );

        $script = $this->scriptBuilder->build($code);

        $context = new ExecutionContext(
            phpVersion: $configuration->phpVersion,
            scriptContent: $script,
            benchmarkClassName: $configuration->benchmark::class,
        );

        return $this->scriptExecutor->executeScript($context);
    }
}
