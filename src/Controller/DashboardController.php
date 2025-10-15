<?php

namespace Jblairy\PhpBenchmark\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Jblairy\PhpBenchmark\Entity\Pulse;
use Jblairy\PhpBenchmark\Model\ChartBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(EntityManagerInterface $entityManager, ChartBuilder $chartBuilder): Response
    {
        // Récupérer les données comme dans la méthode comparison
        $repository = $entityManager->getRepository(Pulse::class);
        $pulses = $repository->findAll();

        // Regrouper les pulses par benchmark (benchId+name) et version PHP
        $benchmarks = [];
        foreach ($pulses as $pulse) {
            $benchmarkKey = $pulse->benchId . '_' . $pulse->name;

            if (!isset($benchmarks[$benchmarkKey])) {
                $benchmarks[$benchmarkKey] = [
                    'benchId' => $pulse->benchId,
                    'name' => $pulse->name,
                    'versions' => []
                ];
            }

            $phpVersion = $pulse->phpVersion->value;
            if (!isset($benchmarks[$benchmarkKey]['versions'][$phpVersion])) {
                $benchmarks[$benchmarkKey]['versions'][$phpVersion] = [
                    'times' => [],
                    'memoryUsed' => [],
                    'memoryPeak' => []
                ];
            }

            $benchmarks[$benchmarkKey]['versions'][$phpVersion]['times'][] = $pulse->executionTimeMs;
            $benchmarks[$benchmarkKey]['versions'][$phpVersion]['memoryUsed'][] = $pulse->memoryUsedBytes;
            $benchmarks[$benchmarkKey]['versions'][$phpVersion]['memoryPeak'][] = $pulse->memoryPeakByte;
        }

        // Calculer les statistiques pour chaque version PHP de chaque benchmark
        $benchmarkStats = [];
        foreach ($benchmarks as $benchmarkKey => $benchmark) {
            $benchmarkStats[$benchmarkKey] = [
                'benchId' => $benchmark['benchId'],
                'name' => $benchmark['name'],
                'phpVersions' => []
            ];

            foreach ($benchmark['versions'] as $phpVersion => $data) {
                sort($data['times']);
                $count = count($data['times']);

                if ($count > 0) {
                    $benchmarkStats[$benchmarkKey]['phpVersions'][$phpVersion] = [
                        'version' => $phpVersion,
                        'count' => $count,
                        'avg' => array_sum($data['times']) / $count,
                        'p50' => $this->percentile($data['times'], 50),
                        'p80' => $this->percentile($data['times'], 80),
                        'p90' => $this->percentile($data['times'], 90),
                        'p95' => $this->percentile($data['times'], 95),
                        'p99' => $this->percentile($data['times'], 99),
                        'memoryUsed' => array_sum($data['memoryUsed']) / $count,
                        'memoryPeak' => max($data['memoryPeak'])
                    ];
                }
            }
        }

        // Récupérer toutes les versions PHP disponibles dans les benchmarks
        $allPhpVersions = [];
        foreach ($benchmarkStats as $benchmark) {
            foreach ($benchmark['phpVersions'] as $version => $stats) {
                if (!in_array($version, $allPhpVersions)) {
                    $allPhpVersions[] = $version;
                }
            }
        }
        sort($allPhpVersions);

        foreach ($benchmarkStats as $key => $benchmark) {
            $benchmarkStats[$key]['chart'] = $chartBuilder->createBenchmarkChart($benchmark, $allPhpVersions);
        }

        return $this->render('dashboard/index.html.twig', [
            'stats' => $benchmarkStats,
            'allPhpVersions' => $allPhpVersions
        ]);
    }

    private function percentile(array $data, int $percentile): float
    {
        $count = count($data);
        if ($count === 0) {
            return 0;
        }

        $index = ceil($percentile / 100 * $count) - 1;
        return $data[$index] ?? end($data);
    }
}
