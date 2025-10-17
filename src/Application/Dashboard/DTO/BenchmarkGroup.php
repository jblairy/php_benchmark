<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\DTO;

/**
 * Data Transfer Object grouping benchmark statistics by PHP version.
 */
final readonly class BenchmarkGroup
{
    /**
     * @param BenchmarkStatisticsData[] $phpVersions Statistics indexed by PHP version
     */
    public function __construct(
        public string $benchmarkId,
        public string $benchmarkName,
        public array $phpVersions,
    ) {
    }
}
