<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Service;

/**
 * Strategy for handling outliers in statistical calculations.
 *
 * Defines how outliers should be treated when calculating benchmark statistics.
 */
interface OutlierHandlingStrategy
{
    /**
     * Select data for analysis based on outlier detection results.
     *
     * @param array<int, float> $cleanedData  Data with outliers removed
     * @param array<int, float> $originalData Original data including outliers
     *
     * @return array<int, float> Data to use for statistical calculations
     */
    public function selectDataForAnalysis(array $cleanedData, array $originalData): array;

    /**
     * Get the count of data points used for analysis.
     */
    public function getAnalysisCount(OutlierDetectionResult $outlierResult, int $originalCount): int;
}
