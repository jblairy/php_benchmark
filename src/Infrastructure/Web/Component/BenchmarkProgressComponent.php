<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Live Component for displaying real-time benchmark progress.
 */
#[AsLiveComponent('BenchmarkProgress')]
final class BenchmarkProgressComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $benchmarkId = '';

    #[LiveProp(writable: true)]
    public string $benchmarkName = '';

    #[LiveProp(writable: true)]
    public string $phpVersion = '';

    #[LiveProp(writable: true)]
    public int $currentIteration = 0;

    #[LiveProp(writable: true)]
    public int $totalIterations = 0;

    #[LiveProp(writable: true)]
    public string $status = 'idle';

    public function getProgress(): int
    {
        if (0 === $this->totalIterations) {
            return 0;
        }

        return (int) (($this->currentIteration / $this->totalIterations) * 100);
    }

    public function isRunning(): bool
    {
        return 'running' === $this->status || 'started' === $this->status;
    }

    public function isCompleted(): bool
    {
        return 'completed' === $this->status;
    }

    public function getMercurePublicUrl(): string
    {
        return $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost:3000/.well-known/mercure';
    }
}
