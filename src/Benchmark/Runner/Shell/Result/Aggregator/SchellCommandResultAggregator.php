<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\Runner\Shell\Result\Aggregator;

use Jblairy\PhpBenchmark\Benchmark\Runner\Shell\Result\SchellCommandResult;

final class SchellCommandResultAggregator extends SchellCommandResult
{
    public function addResult(SchellCommandResult $result): void
    {
        $this->executionTimeMs += $result->executionTimeMs;
        $this->memoryPeakUsageBytes += $result->memoryPeakUsageBytes;
        $this->memoryUsedBytes += $result->memoryUsedBytes;
    }

    public function getResult(int $iterations): SchellCommandResult
    {
        return new SchellCommandResult(
            $this->executionTimeMs / $iterations,
            $this->memoryUsedBytes / $iterations,
            $this->memoryPeakUsageBytes / $iterations,
        );
    }
}
