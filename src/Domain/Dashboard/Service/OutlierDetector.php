<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Service;

/**
 * Service for detecting and removing outliers from benchmark data.
 * Uses Tukey's method (Interquartile Range) for robust outlier detection.
 */
final readonly class OutlierDetector
{
    /**
     * IQR multiplier for outlier detection.
     * 1.5 = standard outliers, 3.0 = extreme outliers.
     */
    private const float IQR_MULTIPLIER = 1.5;

    /**
     * Remove outliers from the dataset using Tukey's method.
     *
     * @param array<int, float> $data
     */
    public function detectAndRemove(array $data): OutlierDetectionResult
    {
        if (4 > count($data)) {
            // Not enough data points for meaningful outlier detection
            return new OutlierDetectionResult(
                cleanedData: $data,
                outliers: [],
                lowerBound: 0.0,
                upperBound: 0.0,
                outlierCount: 0,
                originalCount: count($data),
            );
        }

        $sorted = $data;
        sort($sorted);

        // Calculate quartiles
        $q1 = $this->calculateQuartile($sorted, 0.25);
        $q3 = $this->calculateQuartile($sorted, 0.75);
        $iqr = $q3 - $q1;

        // Calculate bounds
        $lowerBound = $q1 - (self::IQR_MULTIPLIER * $iqr);
        $upperBound = $q3 + (self::IQR_MULTIPLIER * $iqr);

        // Separate outliers and clean data
        $cleanedData = [];
        $outliers = [];

        foreach ($data as $value) {
            if ($value < $lowerBound || $value > $upperBound) {
                $outliers[] = $value;
            } else {
                $cleanedData[] = $value;
            }
        }

        return new OutlierDetectionResult(
            cleanedData: $cleanedData,
            outliers: $outliers,
            lowerBound: $lowerBound,
            upperBound: $upperBound,
            outlierCount: count($outliers),
            originalCount: count($data),
        );
    }

    /**
     * Calculate Modified Z-Score for more robust outlier detection.
     * Alternative method that's more resistant to outliers in the data.
     *
     * @param array<int, float> $data
     *
     * @return array<int, float> Array of modified z-scores
     */
    public function calculateModifiedZScores(array $data): array
    {
        if (2 > count($data)) {
            return array_fill(0, count($data), 0.0);
        }

        $sorted = $data;
        sort($sorted);
        $median = $this->calculateQuartile($sorted, 0.5);

        // Calculate MAD (Median Absolute Deviation)
        $deviations = array_map(fn ($x) => abs($x - $median), $data);
        sort($deviations);
        $mad = $this->calculateQuartile($deviations, 0.5);

        // Avoid division by zero
        if (0.0 === $mad) {
            return array_fill(0, count($data), 0.0);
        }

        // Calculate modified z-scores
        $modifiedZScores = [];
        foreach ($data as $value) {
            $modifiedZScores[] = 0.6745 * ($value - $median) / $mad;
        }

        return $modifiedZScores;
    }

    /**
     * Detect outliers using Modified Z-Score method.
     * Values with |z-score| > 3.5 are considered outliers.
     *
     * @param array<int, float> $data
     */
    public function detectWithModifiedZScore(array $data, float $threshold = 3.5): OutlierDetectionResult
    {
        $zScores = $this->calculateModifiedZScores($data);

        $cleanedData = [];
        $outliers = [];

        foreach ($data as $index => $value) {
            if (abs($zScores[$index]) > $threshold) {
                $outliers[] = $value;
            } else {
                $cleanedData[] = $value;
            }
        }

        return new OutlierDetectionResult(
            cleanedData: $cleanedData,
            outliers: $outliers,
            lowerBound: -$threshold,
            upperBound: $threshold,
            outlierCount: count($outliers),
            originalCount: count($data),
        );
    }

    /**
     * Calculate a specific quartile using linear interpolation.
     *
     * @param array<int, float> $sortedData Must be sorted in ascending order
     * @param float             $percentile Between 0 and 1 (e.g., 0.25 for Q1)
     */
    private function calculateQuartile(array $sortedData, float $percentile): float
    {
        $count = count($sortedData);
        if (0 === $count) {
            return 0.0;
        }

        if (1 === $count) {
            return $sortedData[0];
        }

        // Calculate the position
        $position = ($count - 1) * $percentile;
        $lower = (int) floor($position);
        $upper = (int) ceil($position);

        // If position is an integer, return that value
        if ($lower === $upper) {
            return $sortedData[$lower];
        }

        // Linear interpolation between lower and upper
        $weight = $position - $lower;

        return $sortedData[$lower] * (1 - $weight) + $sortedData[$upper] * $weight;
    }
}
