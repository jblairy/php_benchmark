<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Port;

use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkMetrics;

/**
 * Port for providing dashboard benchmark data.
 *
 * Follows Dependency Inversion Principle: Domain defines the contract,
 * Infrastructure provides the implementation
 */
interface DashboardDataProviderPort
{
    /**
     * Get all benchmark metrics grouped by benchmark and PHP version.
     *
     * @return BenchmarkMetrics[]
     */
    public function getAllBenchmarkMetrics(): array;

    /**
     * Get all unique PHP versions present in benchmarks.
     *
     * @return string[]
     */
    public function getAllPhpVersions(): array;
}
