<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Model;

/**
 * Value Object representing dashboard statistics.
 *
 * Immutable aggregate of benchmark execution statistics for the dashboard overview.
 */
final readonly class DashboardStats
{
    public function __construct(
        public int $totalBenchmarks,
        public int $phpVersionsTested,
        public int $benchmarksExecuted,
        public int $totalExecutions,
    ) {
    }
}
