<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Application\Dashboard\Builder;

use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkGroup;
use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkStatisticsData;

/**
 * Builder for constructing BenchmarkGroup with type-safe PHP version accumulation.
 */
final class BenchmarkGroupBuilder
{
    /**
     * @var array<string, BenchmarkStatisticsData>
     */
    private array $phpVersions = [];

    public function __construct(
        private readonly string $benchmarkId,
        private readonly string $benchmarkName,
    ) {
    }

    public function addPhpVersion(string $phpVersion, BenchmarkStatisticsData $statistics): self
    {
        $this->phpVersions[$phpVersion] = $statistics;

        return $this;
    }

    public function build(): BenchmarkGroup
    {
        return new BenchmarkGroup(
            benchmarkId: $this->benchmarkId,
            benchmarkName: $this->benchmarkName,
            phpVersions: $this->phpVersions,
        );
    }
}
