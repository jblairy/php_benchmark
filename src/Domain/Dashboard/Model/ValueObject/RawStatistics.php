<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject;

/**
 * Value Object representing raw statistics before outlier removal.
 */
final readonly class RawStatistics
{
    public function __construct(
        public int $rawExecutionCount,
        public float $rawAverage,
        public float $rawStdDev,
        public float $rawCV,
    ) {
    }

    public static function create(
        int $rawExecutionCount,
        float $rawAverage,
        float $rawStdDev,
        float $rawCV,
    ): self {
        return new self($rawExecutionCount, $rawAverage, $rawStdDev, $rawCV);
    }

    public static function empty(): self
    {
        return new self(0, 0.0, 0.0, 0.0);
    }

    /**
     * Calculate improvement in CV% compared to cleaned statistics.
     */
    public function calculateCVImprovement(float $cleanedCV): float
    {
        if (0.0 === $this->rawCV) {
            return 0.0;
        }

        return (($this->rawCV - $cleanedCV) / $this->rawCV) * 100.0;
    }
}
