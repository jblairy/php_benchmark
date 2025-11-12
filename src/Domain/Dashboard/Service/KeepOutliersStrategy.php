<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Service;

/**
 * Strategy that keeps outliers in statistical calculations.
 *
 * Uses original data (including outliers) for raw statistics.
 */
final readonly class KeepOutliersStrategy implements OutlierHandlingStrategy
{
    public function selectDataForAnalysis(array $cleanedData, array $originalData): array
    {
        return $originalData;
    }

    public function getAnalysisCount(OutlierDetectionResult $outlierResult, int $originalCount): int
    {
        return $originalCount;
    }
}
