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

final readonly class SingleBenchmarkExecutor implements BenchmarkExecutorPort
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

        $script = $this->scriptBuilderPort->build($code);

        $executionContext = new ExecutionContext(
            phpVersion: $benchmarkConfiguration->phpVersion,
            scriptContent: $script,
            benchmarkClassName: $benchmarkConfiguration->benchmark::class,
        );

        return $this->scriptExecutorPort->executeScript($executionContext);
    }
}
