<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Service;

/**
 * Strategy that removes outliers from statistical calculations.
 *
 * Uses cleaned data (outliers removed) for more stable statistics.
 */
final readonly class RemoveOutliersStrategy implements OutlierHandlingStrategy
{
    public function selectDataForAnalysis(array $cleanedData, array $originalData): array
    {
        return $cleanedData;
    }

    public function getAnalysisCount(OutlierDetectionResult $outlierResult, int $originalCount): int
    {
        return count($outlierResult->cleanedData);
    }
}
