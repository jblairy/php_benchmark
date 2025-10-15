<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkConfiguration;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ResultPersisterPort;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity\Pulse;

final class DoctrinePulseResultPersister implements ResultPersisterPort
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function persist(BenchmarkConfiguration $configuration, BenchmarkResult $result): void
    {
        $pulse = $this->createPulseEntity($configuration, $result);

        $this->entityManager->persist($pulse);
        $this->entityManager->flush();
    }

    private function createPulseEntity(BenchmarkConfiguration $configuration, BenchmarkResult $result): Pulse
    {
        return Pulse::create(
            $result->executionTimeMs,
            $result->memoryUsedBytes,
            $result->memoryPeakBytes,
            $configuration->phpVersion,
            $configuration->benchmark::class,
        );
    }
}
