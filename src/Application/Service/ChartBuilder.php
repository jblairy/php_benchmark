<?php

namespace Jblairy\PhpBenchmark\Application\Service;

use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;

class ChartBuilder
{
    public function __construct(
        private ChartBuilderInterface $chartBuilder
    ) {}

    public function createBenchmarkChart(array $benchmark, array $allPhpVersions): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

        // Préparation des données en conservant l'ordre des versions
        $p50Data = [];
        $p90Data = [];
        $avgData = [];

        foreach ($allPhpVersions as $version) {
            $p50Data[] = $benchmark['phpVersions'][$version]['p50'] ?? null;
            $p90Data[] = $benchmark['phpVersions'][$version]['p90'] ?? null;
            $avgData[] = $benchmark['phpVersions'][$version]['avg'] ?? null;
        }

        $chart->setData([
            'labels' => array_map(fn($version) => 'PHP ' . str_replace('php', '', $version), $allPhpVersions),
            'datasets' => [
                [
                    'label' => 'p50 (ms)',
                    'data' => $p50Data,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1
                ],
                [
                    'label' => 'p90 (ms)',
                    'data' => $p90Data,
                    'backgroundColor' => 'rgba(255, 159, 64, 0.5)',
                    'borderColor' => 'rgba(255, 159, 64, 1)',
                    'borderWidth' => 1
                ],
                [
                    'label' => 'Moyenne (ms)',
                    'data' => $avgData,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.5)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1
                ]
            ]
        ]);

        $chart->setOptions([
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Temps d\'exécution (ms)'
                    ]
                ]
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top'
                ]
            ]
        ]);

        return $chart;
    }
}
