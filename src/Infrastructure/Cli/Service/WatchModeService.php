<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sleep;

/**
 * Service for managing watch mode (continuous monitoring).
 */
final readonly class WatchModeService
{
    public function __construct(
        private DashboardRenderer $dashboardRenderer,
    ) {
    }

    /**
     * Run continuous monitoring loop.
     *
     * @param callable(): void $dashboardCallback Callback to render dashboard content
     */
    public function runWatchLoop(
        SymfonyStyle $symfonyStyle,
        OutputInterface $output,
        callable $dashboardCallback,
        int $intervalSeconds,
    ): void {
        $symfonyStyle->title('Messenger Monitor - Real-time Dashboard');
        $symfonyStyle->note('Press Ctrl+C to stop monitoring');

        // Infinite loop for watch mode - user must press Ctrl+C to stop
        // @phpstan-ignore-next-line while.alwaysTrue - intentional infinite loop
        while (true) {
            $this->dashboardRenderer->clearScreen($output);
            $dashboardCallback();
            sleep($intervalSeconds);
        }
    }
}
