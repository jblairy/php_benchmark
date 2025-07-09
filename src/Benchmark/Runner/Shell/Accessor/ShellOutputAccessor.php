<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\Runner\Shell\Accessor;

use RuntimeException;
use Webmozart\Assert\Assert;

final class ShellOutputAccessor
{
    /** @var array<mixed, mixed> */
    private readonly array $output;

    public function __construct(string $output)
    {
        $data = json_decode($output, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException('Errored while decoding json output.');
        }

        Assert::isArray($data);

        $this->output = $data;
    }

    public function getExecutionTimeMs(): float
    {
        return $this->getFloatValueFromArrayWithIndex('execution_time_ms');
    }

    public function getMemoryUsedBytes(): float
    {
        return $this->getFloatValueFromArrayWithIndex('memory_used_bytes');
    }

    public function getMemoryPeakByte(): float
    {
        return $this->getFloatValueFromArrayWithIndex('memory_peak_bytes');
    }

    private function getFloatValueFromArrayWithIndex(string $index): float
    {
        if (!array_key_exists($index, $this->output)) {
            $this->throwIndexNotFound($index);
        }

        if (!is_numeric($this->output[$index])) {
            $this->throwTypeError();
        }

        return (float) $this->output[$index];
    }

    private function throwIndexNotFound(string $index): never
    {
        throw new RuntimeException(sprintf('Data was not found in the json output. Expected key: %s.', $index));
    }

    private function throwTypeError(): never
    {
        throw new RuntimeException('Float values were not found in the json output.');
    }
}
