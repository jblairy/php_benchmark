<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Controller;

use Jblairy\PhpBenchmark\Application\Dashboard\UseCase\GetDashboardStatistics;
use Jblairy\PhpBenchmark\Infrastructure\Web\Presentation\ChartBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly GetDashboardStatistics $getDashboardStatistics,
        private readonly ChartBuilder $chartBuilder,
    ) {}

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        // Execute use case to get dashboard data
        $dashboardData = $this->getDashboardStatistics->execute();

        // Add charts to each benchmark
        $benchmarkStats = array_map(
            function ($benchmarkGroup) use ($dashboardData) {
                $benchmarkArray = $benchmarkGroup->toArray();
                $benchmarkArray['chart'] = $this->chartBuilder->createBenchmarkChart(
                    $benchmarkArray,
                    $dashboardData->allPhpVersions
                );
                return $benchmarkArray;
            },
            $dashboardData->benchmarks
        );

        // Render view
        return $this->render('dashboard/index.html.twig', [
            'stats' => $benchmarkStats,
            'allPhpVersions' => $dashboardData->allPhpVersions,
        ]);
    }
}
