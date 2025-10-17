<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Controller;

use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Repository\PulseRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly PulseRepositoryInterface $pulseRepository,
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        $benchmarks = $this->pulseRepository->findUniqueBenchmarks();

        return $this->render('dashboard/index.html.twig', [
            'benchmarks' => $benchmarks,
        ]);
    }
}
