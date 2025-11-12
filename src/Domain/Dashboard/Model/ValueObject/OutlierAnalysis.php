<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject;

/**
 * Value Object representing outlier detection and stability analysis.
 */
final readonly class OutlierAnalysis
{
    /**
     * @param array<int, float> $outliers
     */
    public function __construct(
        public int $outlierCount,
        public float $outlierPercentage,
        public array $outliers,
        public float $stabilityScore,
    ) {
    }

    /**
     * @param array<int, float> $outliers
     */
    public static function create(
        int $outlierCount,
        float $outlierPercentage,
        array $outliers,
        float $stabilityScore,
    ): self {
        return new self($outlierCount, $outlierPercentage, $outliers, $stabilityScore);
    }

    public static function empty(): self
    {
        return new self(0, 0.0, [], 0.0);
    }

    /**
     * Get a stability rating based on the stability score.
     */
    public function getStabilityRating(): string
    {
        return match (true) {
            90 <= $this->stabilityScore => 'Excellent',
            75 <= $this->stabilityScore => 'Good',
            60 <= $this->stabilityScore => 'Fair',
            40 <= $this->stabilityScore => 'Poor',
            default => 'Very Poor',
        };
    }
}
