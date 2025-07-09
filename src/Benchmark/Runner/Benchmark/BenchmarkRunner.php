<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark;

use Jblairy\PhpBenchmark\Benchmark\Benchmark;
use Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark\Configurator\Configurator;
use Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark\Result\BenchmarkResult;
use Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark\Result\BenchmarkResultCollection;
use Jblairy\PhpBenchmark\Benchmark\Runner\Shell\Result\Aggregator\SchellCommandResultAggregator;
use Jblairy\PhpBenchmark\Benchmark\Runner\Shell\ShellCommandRunner;
use Jblairy\PhpBenchmark\Benchmark\ScriptBuilder\ScriptBuilder;
use Jblairy\PhpBenchmark\PhpVersion\Enum\PhpVersion;

class BenchmarkRunner
{
    private BenchmarkResultCollection $results;
    private SchellCommandResultAggregator $resultAggregator;

    public function __construct(
        private Configurator $configurator,
    ) {
        $this->resultAggregator = new SchellCommandResultAggregator();
        $this->results = new BenchmarkResultCollection();
    }

    public function execute(): void
    {
        foreach ($this->configurator->getBenchmarks() as $benchmark) {
            $this->runBenchmark($benchmark);
        }
    }

    public function runBenchmark(Benchmark $benchmark): void
    {
        foreach ($this->configurator->getPhpVersion() as $phpVersion) {
            $commandRunner = $this->getCommandRunner($benchmark->getMethodBody($phpVersion), $phpVersion);

            for ($i = 0; $i < $this->configurator->getIterations(); ++$i) {
                $this->resultAggregator->addResult($commandRunner->executeScript());
            }

            $this->results->append(
                new BenchmarkResult(
                    $this->resultAggregator->getResult($this->configurator->getIterations()),
                    $phpVersion,
                    $benchmark::class,
                ),
            );
        }
    }

    public function getResults(): BenchmarkResultCollection
    {
        return $this->results;
    }

    private function getCommandRunner(string $methodBody, PhpVersion $phpVersion): ShellCommandRunner
    {
        $script = ScriptBuilder::fromMethodBody($methodBody)
            ->withIterations($this->configurator->getIterations())
            ->build();

        return ShellCommandRunner::fromPhpVersion($phpVersion)
            ->withScript($script);
    }
}
