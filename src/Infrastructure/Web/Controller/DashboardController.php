<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Controller;

use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Psr\Log\LoggerInterface;
use RuntimeException;
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
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        try {
            $stats = $this->benchmarkRepositoryPort->getDashboardStats();
            $topCategories = $this->benchmarkRepositoryPort->getTopCategories(3);
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to load dashboard stats', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            $this->addFlash('error', 'Failed to load dashboard statistics');

            return $this->redirectToRoute('app_dashboard');
        }

        $response = $this->render('dashboard/index.html.twig', [
            'mercure_public_url' => $this->mercurePublicUrl,
            'stats' => $stats,
            'top_categories' => $topCategories,
        ]);

        // Cache for 5 minutes
        $response->setSharedMaxAge(300);
        $response->setPublic();

        return $response;
    }
}
