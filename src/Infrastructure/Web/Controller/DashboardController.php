<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Controller;

use Jblairy\PhpBenchmark\Application\Dashboard\UseCase\GetDashboardStatistics;
use Jblairy\PhpBenchmark\Infrastructure\Web\Presentation\BenchmarkPresentation;
use Jblairy\PhpBenchmark\Infrastructure\Web\Presentation\ChartBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly GetDashboardStatistics $getDashboardStatistics,
        private readonly ChartBuilder $chartBuilder,
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        $dashboardData = $this->getDashboardStatistics->execute();

        $benchmarkPresentations = array_map(
            fn ($benchmarkGroup) => BenchmarkPresentation::fromBenchmarkGroup(
                $benchmarkGroup,
                $this->chartBuilder->createBenchmarkChart($benchmarkGroup, $dashboardData->allPhpVersions),
            ),
            $dashboardData->benchmarks,
        );

        return $this->render('dashboard/index.html.twig', [
            'stats' => $benchmarkPresentations,
            'allPhpVersions' => $dashboardData->allPhpVersions,
        ]);
    }
}
