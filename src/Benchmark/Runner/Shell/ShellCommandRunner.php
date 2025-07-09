<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\Runner\Shell;

use Jblairy\PhpBenchmark\Benchmark\Runner\Shell\Accessor\ShellOutputAccessor;
use Jblairy\PhpBenchmark\Benchmark\Runner\Shell\Result\SchellCommandResult;
use Jblairy\PhpBenchmark\PhpVersion\Enum\PhpVersion;
use RuntimeException;

final class ShellCommandRunner
{
    private string $script = '';

    private function __construct(private PhpVersion $phpVersion)
    {
    }

    public static function fromPhpVersion(PhpVersion $phpVersion): ShellCommandRunner
    {
        return new ShellCommandRunner($phpVersion);
    }

    public function withScript(string $script): ShellCommandRunner
    {
        $this->script = $script;

        return $this;
    }

    public function executeScript(): SchellCommandResult
    {
        $output = [];
        $exitCode = 0;

        exec(sprintf(
            'docker-compose exec -T %s php -r \'%s\'', // TODO escape problem
            $this->phpVersion->value,
            $this->script,
        ), $output, $exitCode);

        if (0 !== $exitCode) {
            throw new RuntimeException('Errored while executing script.');
        }

        $outputAccessor = new ShellOutputAccessor(implode('', $output));

        return
            new SchellCommandResult(
                $outputAccessor->getExecutionTimeMs(),
                $outputAccessor->getMemoryUsedBytes(),
                $outputAccessor->getMemoryPeakByte(),
            );
    }
}
