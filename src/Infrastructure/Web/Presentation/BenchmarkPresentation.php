<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Presentation;

use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkGroup;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * ViewModel combining benchmark data with its chart for web presentation.
 *
 * Follows Presentation Model pattern: combines Application DTOs with Infrastructure concerns.
 */
final readonly class BenchmarkPresentation
{
    public function __construct(
        public string $benchmarkId,
        public string $benchmarkName,
        public array $phpVersions,
        public Chart $chart,
    ) {
    }

    public static function fromBenchmarkGroup(BenchmarkGroup $group, Chart $chart): self
    {
        return new self(
            benchmarkId: $group->benchmarkId,
            benchmarkName: $group->benchmarkName,
            phpVersions: $group->phpVersions,
            chart: $chart,
        );
    }
}
