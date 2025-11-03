<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ResultPersisterPort;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Adapter\DatabaseBenchmark;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity\Pulse;

final readonly class DoctrinePulseResultPersister implements ResultPersisterPort
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function persist(BenchmarkConfiguration $benchmarkConfiguration, BenchmarkResult $benchmarkResult): void
    {
        $pulse = $this->createPulseEntity($benchmarkConfiguration, $benchmarkResult);

        $this->entityManager->persist($pulse);
        $this->entityManager->flush();
    }

    private function createPulseEntity(BenchmarkConfiguration $benchmarkConfiguration, BenchmarkResult $benchmarkResult): Pulse
    {
        $benchmarkIdentifier = $this->resolveBenchmarkIdentifier($benchmarkConfiguration->benchmark);

        return Pulse::create(
            $benchmarkResult->executionTimeMs,
            $benchmarkResult->memoryUsedBytes,
            $benchmarkResult->memoryPeakBytes,
            $benchmarkConfiguration->phpVersion,
            $benchmarkIdentifier,
        );
    }

    private function resolveBenchmarkIdentifier(Benchmark $benchmark): string
    {
        if ($benchmark instanceof DatabaseBenchmark) {
            return $benchmark->getSlug();
        }

        return $benchmark::class;
    }
}
