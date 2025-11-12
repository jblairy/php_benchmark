<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Service;

/**
 * Service for detecting and removing outliers from benchmark data.
 * Uses Tukey's method (Interquartile Range) for robust outlier detection.
 */
final readonly class OutlierDetector
{
    private const float IQR_MULTIPLIER = 1.5;

    /**
     * Remove outliers from the dataset using Tukey's method.
     *
     * @param array<int, float> $data
     */
    public function detectAndRemove(array $data): OutlierDetectionResult
    {
        if ($this->hasInsufficientDataPoints($data)) {
            return $this->createEmptyResult($data);
        }

        $sorted = $data;
        sort($sorted);

        $bounds = $this->calculateOutlierBounds($sorted);
        $classification = $this->classifyDataPoints($data, $bounds);

        return new OutlierDetectionResult(
            cleanedData: $classification['clean'],
            outliers: $classification['outliers'],
            lowerBound: $bounds['lower'],
            upperBound: $bounds['upper'],
            outlierCount: count($classification['outliers']),
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

        $medianAbsoluteDeviation = $this->calculateMedianAbsoluteDeviation($data, $median);

        if (0.0 === $medianAbsoluteDeviation) {
            return array_fill(0, count($data), 0.0);
        }

        return $this->calculateZScoresFromMAD($data, $median, $medianAbsoluteDeviation);
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
            $zScore = $zScores[$index] ?? 0.0;

            if (abs($zScore) > $threshold) {
                $outliers[] = $value;

                continue;
            }

            $cleanedData[] = $value;
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
     * @param array<int, float> $data
     */
    private function hasInsufficientDataPoints(array $data): bool
    {
        return 4 > count($data);
    }

    /**
     * @param array<int, float> $data
     */
    private function createEmptyResult(array $data): OutlierDetectionResult
    {
        return new OutlierDetectionResult(
            cleanedData: $data,
            outliers: [],
            lowerBound: 0.0,
            upperBound: 0.0,
            outlierCount: 0,
            originalCount: count($data),
        );
    }

    /**
     * @param array<int, float> $sortedData
     *
     * @return array{lower: float, upper: float}
     */
    private function calculateOutlierBounds(array $sortedData): array
    {
        $firstQuartile = $this->calculateQuartile($sortedData, 0.25);
        $thirdQuartile = $this->calculateQuartile($sortedData, 0.75);
        $interquartileRange = $thirdQuartile - $firstQuartile;

        return [
            'lower' => $firstQuartile - (self::IQR_MULTIPLIER * $interquartileRange),
            'upper' => $thirdQuartile + (self::IQR_MULTIPLIER * $interquartileRange),
        ];
    }

    /**
     * @param array<int, float>                 $data
     * @param array{lower: float, upper: float} $bounds
     *
     * @return array{clean: array<int, float>, outliers: array<int, float>}
     */
    private function classifyDataPoints(array $data, array $bounds): array
    {
        $clean = [];
        $outliers = [];

        foreach ($data as $key => $value) {
            if ($this->isOutlier($value, $bounds)) {
                $outliers[$key] = $value;

                continue;
            }

            $clean[$key] = $value;
        }

        return compact('clean', 'outliers');
    }

    /**
     * @param array{lower: float, upper: float} $bounds
     */
    private function isOutlier(float $value, array $bounds): bool
    {
        return $value < $bounds['lower'] || $value > $bounds['upper'];
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
            return $sortedData[0] ?? 0.0;
        }

        $position = ($count - 1) * $percentile;
        $lower = (int) floor($position);
        $upper = (int) ceil($position);

        if ($lower === $upper) {
            return $sortedData[$lower] ?? 0.0;
        }

        $weight = $position - $lower;
        $lowerValue = $sortedData[$lower] ?? 0.0;
        $upperValue = $sortedData[$upper] ?? 0.0;

        return $lowerValue * (1 - $weight) + $upperValue * $weight;
    }

    /**
     * @param array<int, float> $data
     */
    private function calculateMedianAbsoluteDeviation(array $data, float $median): float
    {
        $deviations = array_map(fn (float $value): float => abs($value - $median), $data);
        sort($deviations);

        return $this->calculateQuartile($deviations, 0.5);
    }

    /**
     * @param array<int, float> $data
     *
     * @return array<int, float>
     */
    private function calculateZScoresFromMAD(array $data, float $median, float $mad): array
    {
        $modifiedZScores = [];
        foreach ($data as $value) {
            $modifiedZScores[] = 0.6745 * ($value - $median) / $mad;
        }

        return $modifiedZScores;
    }
}
