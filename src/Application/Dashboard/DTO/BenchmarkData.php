<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\DTO;

/**
 * DTO containing statistics for a single benchmark across all PHP versions.
 */
final readonly class BenchmarkData
{
    /**
     * @param array<string, BenchmarkStatisticsData> $phpVersions Statistics indexed by PHP version
     */
    public function __construct(
        public string $benchmarkId,
        public string $benchmarkName,
        public array $phpVersions,
    ) {
    }
}
