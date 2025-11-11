<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Presentation;

/**
 * Value Object for Chart.js color palette.
 *
 * Centralizes chart colors for consistency across the application.
 */
final readonly class ChartColor
{
    // P50 (Median) - Blue
    public const string P50_BACKGROUND = 'rgba(54, 162, 235, 0.5)';

    public const string P50_BORDER = 'rgba(54, 162, 235, 1)';

    // P90 (90th percentile) - Orange
    public const string P90_BACKGROUND = 'rgba(255, 159, 64, 0.5)';

    public const string P90_BORDER = 'rgba(255, 159, 64, 1)';

    // P99 (99th percentile) - Red
    public const string P99_BACKGROUND = 'rgba(255, 99, 132, 0.5)';

    public const string P99_BORDER = 'rgba(255, 99, 132, 1)';

    // Average - Teal
    public const string AVG_BACKGROUND = 'rgba(75, 192, 192, 0.5)';

    public const string AVG_BORDER = 'rgba(75, 192, 192, 1)';
}
