<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli;

use Exception;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\DashboardRenderer;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\QueueMonitorService;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\RedisConnectionService;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\RedisPerformanceService;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\WatchModeService;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\WorkerStatusService;
use Redis;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function is_numeric;
use function sprintf;

#[AsCommand(
    name: 'messenger:monitor',
    description: 'Monitor Messenger workers and queue status in real-time',
)]
final readonly class MessengerMonitorCommand
{
    public function __construct(
        private RedisConnectionService $redisConnectionService,
        private QueueMonitorService $queueMonitorService,
        private RedisPerformanceService $redisPerformanceService,
        private WorkerStatusService $workerStatusService,
        private DashboardRenderer $dashboardRenderer,
        private WatchModeService $watchModeService,
    ) {
    }

    public function __invoke(
        #[\Symfony\Component\Console\Attribute\Option(name: 'interval', shortcut: 'i', description: 'Refresh interval in seconds')]
        string $interval = '2',
        #[\Symfony\Component\Console\Attribute\Option(name: 'watch', shortcut: 'w', description: 'Watch mode (continuous monitoring)')]
        bool $watch = false,
        ?OutputInterface $output = null,
        ?SymfonyStyle $symfonyStyle = null,
    ): int {
        if (null === $symfonyStyle || null === $output) {
            return Command::FAILURE;
        }

        $intervalSeconds = $this->parseInterval($interval);

        try {
            $redis = $this->redisConnectionService->connect();
        } catch (Exception $exception) {
            $symfonyStyle->error(sprintf('Redis connection failed: %s', $exception->getMessage()));

            return Command::FAILURE;
        }

        if ($watch) {
            $this->runWatchMode($symfonyStyle, $output, $redis, $intervalSeconds);
        } else {
            $this->showDashboard($symfonyStyle, $output, $redis);
        }

        return Command::SUCCESS;
    }

    private function parseInterval(string $interval): int
    {
        return is_numeric($interval) ? (int) $interval : 2;
    }

    private function runWatchMode(
        SymfonyStyle $symfonyStyle,
        OutputInterface $output,
        Redis $redis,
        int $intervalSeconds,
    ): void {
        $this->watchModeService->runWatchLoop(
            $symfonyStyle,
            $output,
            fn () => $this->showDashboard($symfonyStyle, $output, $redis),
            $intervalSeconds,
        );
    }

    private function showDashboard(
        SymfonyStyle $symfonyStyle,
        OutputInterface $output,
        Redis $redis,
    ): void {
        $this->dashboardRenderer->renderHeader($symfonyStyle);

        $this->renderQueueSection($symfonyStyle, $output, $redis);
        $symfonyStyle->newLine();

        $this->renderRedisSection($symfonyStyle, $redis);
        $symfonyStyle->newLine();

        $this->renderWorkerSection($symfonyStyle, $redis);
    }

    private function renderQueueSection(
        SymfonyStyle $symfonyStyle,
        OutputInterface $output,
        Redis $redis,
    ): void {
        $stats = $this->queueMonitorService->getQueueStats($redis);
        $totalPending = $this->queueMonitorService->getTotalPending($stats);

        $this->dashboardRenderer->renderQueueStats($symfonyStyle, $output, $stats);
        $this->dashboardRenderer->renderQueueStatusMessages($symfonyStyle, $totalPending, $stats['failed']);
    }

    private function renderRedisSection(SymfonyStyle $symfonyStyle, Redis $redis): void
    {
        $redisStats = $this->redisPerformanceService->getPerformanceStats($redis);
        $this->dashboardRenderer->renderRedisStats($symfonyStyle, $redisStats);
    }

    private function renderWorkerSection(SymfonyStyle $symfonyStyle, Redis $redis): void
    {
        $activeWorkers = $this->workerStatusService->getActiveWorkerCount($redis);
        $this->dashboardRenderer->renderWorkerStatus($symfonyStyle, $activeWorkers);
    }
}
