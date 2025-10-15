<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\DTO;

/**
 * Data Transfer Object for dashboard display
 */
final readonly class DashboardData
{
    /**
     * @param BenchmarkGroup[] $benchmarks
     * @param string[] $allPhpVersions
     */
    public function __construct(
        public array $benchmarks,
        public array $allPhpVersions,
    ) {}
}
