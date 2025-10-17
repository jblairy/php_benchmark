<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine;

use Jblairy\PhpBenchmark\Domain\Dashboard\Port\DashboardDataProviderPort;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Repository\PulseRepositoryInterface;

/**
 * Doctrine adapter implementing DashboardDataProviderPort, implements interface from Domain
 */
final readonly class DoctrineDashboardDataProvider implements DashboardDataProviderPort
{
    public function __construct(
        private PulseRepositoryInterface $pulseRepository,
    ) {
    }

    public function getAllBenchmarkMetrics(): array
    {
        return $this->pulseRepository->findAllGroupedMetrics();
    }
}
