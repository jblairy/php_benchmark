<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Controller;

use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly BenchmarkRepositoryPort $benchmarkRepository,
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        $stats = $this->benchmarkRepository->getDashboardStats();

        return $this->render('dashboard/index.html.twig', [
            'mercure_public_url' => $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost:3000/.well-known/mercure',
            'total_benchmarks' => $stats->totalBenchmarks,
            'php_versions_tested' => $stats->phpVersionsTested,
            'benchmarks_executed' => $stats->benchmarksExecuted,
            'total_executions' => $stats->totalExecutions,
        ]);
    }
}
