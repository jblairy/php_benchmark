<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Component;

use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkData;
use Jblairy\PhpBenchmark\Application\Dashboard\UseCase\GetBenchmarkStatistics;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use Jblairy\PhpBenchmark\Infrastructure\Web\Presentation\ChartBuilder;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class BenchmarkCardComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $benchmarkId;

    #[LiveProp]
    public string $benchmarkName;

    public ?BenchmarkData $data = null;
    public ?Chart $chart = null;

    public function __construct(
        private readonly GetBenchmarkStatistics $getBenchmarkStatistics,
        private readonly ChartBuilder $chartBuilder,
    ) {
    }

    public function mount(): void
    {
        $this->data = $this->getBenchmarkStatistics->execute($this->benchmarkId, $this->benchmarkName);
        $this->chart = $this->chartBuilder->createBenchmarkChart($this->data, $this->getAllPhpVersions());
    }

    /**
     * @return string[]
     */
    private function getAllPhpVersions(): array
    {
        return array_map(fn (PhpVersion $version): string => $version->value, PhpVersion::cases());
    }
}
