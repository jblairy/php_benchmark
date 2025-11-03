<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\DTO;

/**
 * Data Transfer Object for dashboard overview statistics.
 *
 * Used to transfer aggregated statistics from repository to controller
 * following the "NEW" object approach from Doctrine queries.
 */
final readonly class DashboardStatsData
{
    public function __construct(
        public int $totalBenchmarks,
        public int $phpVersionsTested,
        public int $benchmarksExecuted,
        public int $totalExecutions,
    ) {
    }
}
