<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark;

use Doctrine\ORM\EntityManagerInterface;
use Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark\Configurator\Configurator;
use Jblairy\PhpBenchmark\Benchmark\Runner\Shell\Result\SchellCommandResult;
use Jblairy\PhpBenchmark\Benchmark\Runner\Shell\ShellCommandRunner;
use Jblairy\PhpBenchmark\Benchmark\ScriptBuilder\ScriptBuilder;
use Jblairy\PhpBenchmark\Entity\Pulse;
use Spatie\Async\Pool;

final class BenchmarkRunner
{
    private Configurator $configurator;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ScriptBuilder $scriptBuilder,
        private readonly ShellCommandRunner $shellCommandRunner,
    ) {
        $this->configurator = new Configurator([]);
    }

    public function execute(): void
    {
        $commandRunner = $this->getCommandRunner();

        $pool = Pool::create()->concurrency(100);

        for ($i = 0; $i < $this->configurator->getIterations(); ++$i) {
            $pool->add(function () use ($commandRunner) {
                return $commandRunner->executeScript();
            })->then(function (SchellCommandResult $result) {
                $this->entityManager->persist(Pulse::createFromShellCommandResult(
                    $result,
                    $this->configurator->getPhpVersion(),
                    $this->configurator->getBenchmark()::class,
                ));
                $this->entityManager->flush();
            });
        }

        $pool->wait();
    }

    public function configure(Configurator $configurator): void
    {
        $this->configurator = $configurator;
    }

    private function getCommandRunner(): ShellCommandRunner
    {
        $methodBody = $this->configurator->getBenchmarkMethodBody();
        $phpVersion = $this->configurator->getPhpVersion();

        $script = $this->scriptBuilder
            ->withBody($methodBody)
            ->build()
        ;

        return $this->shellCommandRunner
            ->withPhpVersion($phpVersion)
            ->withScript($script)
            ;
    }
}
