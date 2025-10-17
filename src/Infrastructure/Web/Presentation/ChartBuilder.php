<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Presentation;

use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkGroup;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Chart builder using Symfony UX Chartjs.
 */
final readonly class ChartBuilder
{
    public function __construct(
        private ChartBuilderInterface $chartBuilder,
    ) {
    }

    /**
     * @param string[] $allPhpVersions All available PHP versions
     */
    public function createBenchmarkChart(BenchmarkGroup $benchmarkGroup, array $allPhpVersions): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

        [$p50Data, $p90Data, $avgData] = $this->prepareChartData($benchmarkGroup, $allPhpVersions);

        $chart->setData([
            'labels' => $this->formatVersionLabels($allPhpVersions),
            'datasets' => [
                $this->createDataset('p50 (ms)', $p50Data, 'rgba(54, 162, 235, 0.5)', 'rgba(54, 162, 235, 1)'),
                $this->createDataset('p90 (ms)', $p90Data, 'rgba(255, 159, 64, 0.5)', 'rgba(255, 159, 64, 1)'),
                $this->createDataset('Average (ms)', $avgData, 'rgba(75, 192, 192, 0.5)', 'rgba(75, 192, 192, 1)'),
            ],
        ]);

        $chart->setOptions($this->getChartOptions());

        return $chart;
    }

    private function prepareChartData(BenchmarkGroup $benchmarkGroup, array $allPhpVersions): array
    {
        $p50Data = [];
        $p90Data = [];
        $avgData = [];

        foreach ($allPhpVersions as $phpVersion) {
            $stats = $benchmarkGroup->phpVersions[$phpVersion] ?? null;
            $p50Data[] = $stats?->p50;
            $p90Data[] = $stats?->p90;
            $avgData[] = $stats?->avg;
        }

        return [$p50Data, $p90Data, $avgData];
    }

    private function formatVersionLabels(array $versions): array
    {
        return array_map(
            fn (string $version) => 'PHP ' . str_replace('php', '', $version),
            $versions,
        );
    }

    private function createDataset(string $label, array $data, string $bgColor, string $borderColor): array
    {
        return [
            'label' => $label,
            'data' => $data,
            'backgroundColor' => $bgColor,
            'borderColor' => $borderColor,
            'borderWidth' => 1,
        ];
    }

    private function getChartOptions(): array
    {
        return [
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Execution time (ms)',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
            ],
        ];
    }
}
