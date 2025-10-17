<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Component;

use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Repository\PulseRepositoryInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Live Component for asynchronously loading the benchmark list.
 */
#[AsLiveComponent('BenchmarkList')]
final class BenchmarkListComponent
{
    use DefaultActionTrait;

    /**
     * @var array<int, array{benchId: string, name: string}>|null
     */
    private ?array $benchmarks = null;

    public function __construct(
        private readonly PulseRepositoryInterface $pulseRepository,
    ) {
    }

    /**
     * @return array<int, array{benchId: string, name: string}>
     */
    public function getBenchmarks(): array
    {
        if (null === $this->benchmarks) {
            $this->benchmarks = $this->pulseRepository->findUniqueBenchmarks();
        }

        return $this->benchmarks;
    }
}
