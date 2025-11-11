<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Controller;

use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly BenchmarkRepositoryPort $benchmarkRepositoryPort,
        #[Autowire(env: 'MERCURE_PUBLIC_URL')]
        private readonly string $mercurePublicUrl,
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'mercure_public_url' => $this->mercurePublicUrl,
            'stats' => $this->benchmarkRepositoryPort->getDashboardStats(),
            'top_categories' => $this->benchmarkRepositoryPort->getTopCategories(3),
        ]);
    }
}
