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

#[AsLiveComponent('BenchmarkCard')]
final class BenchmarkCardComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $benchmarkId = '';

    #[LiveProp]
    public string $benchmarkName = '';

    private ?BenchmarkData $data = null;
    private ?Chart $chart = null;

    public function __construct(
        private readonly GetBenchmarkStatistics $getBenchmarkStatistics,
        private readonly ChartBuilder $chartBuilder,
    ) {
    }

    public function getData(): ?BenchmarkData
    {
        if (null === $this->data && '' !== $this->benchmarkId && '' !== $this->benchmarkName) {
            $this->data = $this->getBenchmarkStatistics->execute($this->benchmarkId, $this->benchmarkName);
        }

        return $this->data;
    }

    public function getChart(): ?Chart
    {
        if (null === $this->chart && null !== $this->getData()) {
            $this->chart = $this->chartBuilder->createBenchmarkChart(
                $this->getData(),
                $this->getAllPhpVersions(),
            );
        }

        return $this->chart;
    }

    /**
     * @return string[]
     */
    private function getAllPhpVersions(): array
    {
        return array_map(fn (PhpVersion $version): string => $version->value, PhpVersion::cases());
    }
}
