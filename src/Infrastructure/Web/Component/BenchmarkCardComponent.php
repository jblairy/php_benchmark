<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Component;

use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkData;
use Jblairy\PhpBenchmark\Application\Dashboard\UseCase\GetBenchmarkStatistics;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Adapter\DatabaseBenchmark;
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
    private ?DatabaseBenchmark $benchmark = null;

    public function __construct(
        private readonly GetBenchmarkStatistics $getBenchmarkStatistics,
        private readonly ChartBuilder $chartBuilder,
        private readonly BenchmarkRepositoryPort $benchmarkRepository,
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

    public function getCategory(): string
    {
        return $this->getBenchmark()?->getCategory() ?? '';
    }

    public function getShortName(): string
    {
        return $this->getBenchmark()?->getName() ?? $this->benchmarkId;
    }

    public function getDescription(): string
    {
        return $this->getBenchmark()?->getDescription() ?? '';
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->getBenchmark()?->getTags() ?? [];
    }

    public function getIcon(): ?string
    {
        return $this->getBenchmark()?->getIcon();
    }

    public function getSourceCode(): string
    {
        return $this->getBenchmark()?->getEntity()->getCode() ?? '';
    }

    /**
     * @return string[]
     */
    private function getAllPhpVersions(): array
    {
        return array_map(fn (PhpVersion $version): string => $version->value, PhpVersion::cases());
    }

    private function getBenchmark(): ?DatabaseBenchmark
    {
        if (null === $this->benchmark && '' !== $this->benchmarkId) {
            $found = $this->benchmarkRepository->findBenchmarkByName($this->benchmarkId);
            if ($found instanceof DatabaseBenchmark) {
                $this->benchmark = $found;
            }
        }

        return $this->benchmark;
    }
}
