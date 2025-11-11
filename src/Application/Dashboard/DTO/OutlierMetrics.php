<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\DTO;

/**
 * Value Object representing outlier detection metrics.
 */
final readonly class OutlierMetrics
{
    public function __construct(
        public ?int $outlierCount,
        public ?float $outlierPercentage,
        public ?float $rawCV,
        public ?float $stabilityScore,
        public ?string $stabilityRating,
    ) {
    }

    public function hasOutliers(): bool
    {
        return null !== $this->outlierCount && 0 < $this->outlierCount;
    }

    public function getOutlierInfo(): string
    {
        if (!$this->hasOutliers()) {
            return 'No outliers';
        }

        return sprintf(
            '%d outliers (%.1f%%) removed',
            $this->outlierCount,
            $this->outlierPercentage ?? 0,
        );
    }

    public function getCVImprovement(float $currentCV): ?float
    {
        if (null === $this->rawCV || 0.0 === $this->rawCV) {
            return null;
        }

        return (($this->rawCV - $currentCV) / $this->rawCV) * 100;
    }

    public function getStabilityScoreColor(): string
    {
        if (null === $this->stabilityScore) {
            return 'secondary';
        }

        return match (true) {
            90 <= $this->stabilityScore => 'success',
            75 <= $this->stabilityScore => 'info',
            60 <= $this->stabilityScore => 'warning',
            default => 'danger',
        };
    }
}
