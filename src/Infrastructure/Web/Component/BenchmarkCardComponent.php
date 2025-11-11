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

    private ?BenchmarkData $benchmarkData = null;

    private ?Chart $chart = null;

    private ?DatabaseBenchmark $databaseBenchmark = null;

    public function __construct(
        private readonly GetBenchmarkStatistics $getBenchmarkStatistics,
        private readonly ChartBuilder $chartBuilder,
        private readonly BenchmarkRepositoryPort $benchmarkRepositoryPort,
    ) {
    }

    public function getData(): ?BenchmarkData
    {
        if (null === $this->benchmarkData && '' !== $this->benchmarkId && '' !== $this->benchmarkName) {
            $this->benchmarkData = $this->getBenchmarkStatistics->execute($this->benchmarkId, $this->benchmarkName);
        }

        return $this->benchmarkData;
    }

    public function getChart(): ?Chart
    {
        if (!$this->chart instanceof Chart && $this->getData() instanceof BenchmarkData) {
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
        return array_map(fn (PhpVersion $phpVersion): string => $phpVersion->value, PhpVersion::cases());
    }

    private function getBenchmark(): ?DatabaseBenchmark
    {
        if (null === $this->databaseBenchmark && '' !== $this->benchmarkId) {
            $found = $this->benchmarkRepositoryPort->findBenchmarkByName($this->benchmarkId);
            if ($found instanceof DatabaseBenchmark) {
                $this->databaseBenchmark = $found;
            }
        }

        return $this->databaseBenchmark;
    }
}
