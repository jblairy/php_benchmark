<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service\ValueObject;

/**
 * Value object representing the result of a benchmark calibration.
 */
final readonly class CalibrationResult
{
    public function __construct(
        public string $benchmark,
        public float $measuredTime,
        public int $suggestedWarmup,
        public int $suggestedInner,
        public float $efficiency,
    ) {
    }

    /**
     * @return array{benchmark: string, measured_time: float, suggested_warmup: int, suggested_inner: int, efficiency: float}
     */
    public function toArray(): array
    {
        return [
            'benchmark' => $this->benchmark,
            'measured_time' => $this->measuredTime,
            'suggested_warmup' => $this->suggestedWarmup,
            'suggested_inner' => $this->suggestedInner,
            'efficiency' => $this->efficiency,
        ];
    }
}
