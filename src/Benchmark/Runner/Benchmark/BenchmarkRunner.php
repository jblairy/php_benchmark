<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark;

use Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark\Configurator\Configurator;
use Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark\Result\BenchmarkResult;
use Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark\Result\BenchmarkResultCollection;
use Jblairy\PhpBenchmark\Benchmark\Runner\Shell\Result\Aggregator\SchellCommandResultAggregator;
use Jblairy\PhpBenchmark\Benchmark\Runner\Shell\Result\SchellCommandResult;
use Jblairy\PhpBenchmark\Benchmark\Runner\Shell\ShellCommandRunner;
use Jblairy\PhpBenchmark\Benchmark\ScriptBuilder\ScriptBuilder;
use Spatie\Async\Pool;

class BenchmarkRunner
{
    private BenchmarkResultCollection $results;
    private Configurator $configurator;

    public function __construct(
    ) {
        $this->configurator = new Configurator([]);
        $this->results = new BenchmarkResultCollection();
    }

    public function execute(): void
    {
        $resultAggregator = new SchellCommandResultAggregator();
        $commandRunner = $this->getCommandRunner();

        $pool = Pool::create()->concurrency(100);

        for ($i = 0; $i < $this->configurator->getIterations(); ++$i) {
            $pool->add(function () use ($commandRunner) {
                return $commandRunner->executeScript();
            })->then(function (SchellCommandResult $result) use (&$resultAggregator) {
                $resultAggregator->addResult($result);
            });
        }

        $pool->wait();

        $this->results->append(
            new BenchmarkResult(
                $resultAggregator->getResult($this->configurator->getIterations()),
                $this->configurator->getPhpVersion(),
                $this->configurator->getBenchmark()::class,
            ),
        );
    }

    public function getResults(): BenchmarkResultCollection
    {
        return $this->results;
    }

    public function configure(Configurator $configurator): void
    {
        $this->configurator = $configurator;
    }

    private function getCommandRunner(): ShellCommandRunner
    {
        $methodBody = $this->configurator->getBenchmarkMethodBody();
        $phpVersion = $this->configurator->getPhpVersion();

        $script = ScriptBuilder::fromMethodBody($methodBody)
            ->build();

        return ShellCommandRunner::fromPhpVersion($phpVersion)
            ->withScript($script);
    }
}
