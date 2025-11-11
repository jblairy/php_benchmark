<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Service;

/**
 * Value Object containing the results of outlier detection.
 */
final readonly class OutlierDetectionResult
{
    /**
     * @param array<int, float> $cleanedData Data with outliers removed
     * @param array<int, float> $outliers Detected outlier values
     */
    public function __construct(
        public array $cleanedData,
        public array $outliers,
        public float $lowerBound,
        public float $upperBound,
        public int $outlierCount,
        public int $originalCount,
    ) {
    }

    public function getOutlierPercentage(): float
    {
        if (0 === $this->originalCount) {
            return 0.0;
        }

        return ($this->outlierCount / $this->originalCount) * 100.0;
    }

    public function hasOutliers(): bool
    {
        return $this->outlierCount > 0;
    }

    public function getCleanedCount(): int
    {
        return count($this->cleanedData);
    }

    /**
     * Get a summary of the outlier detection.
     */
    public function getSummary(): string
    {
        if (!$this->hasOutliers()) {
            return sprintf('No outliers detected in %d samples', $this->originalCount);
        }

        return sprintf(
            'Detected %d outliers (%.1f%%) in %d samples. Bounds: [%.4f, %.4f]',
            $this->outlierCount,
            $this->getOutlierPercentage(),
            $this->originalCount,
            $this->lowerBound,
            $this->upperBound,
        );
    }
}