<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Twig Component for displaying real-time benchmark progress via Mercure.
 * Updated via JavaScript/Mercure events, not server-side rendering.
 */
#[AsTwigComponent('BenchmarkProgress')]
final class BenchmarkProgressComponent
{
    public function getMercurePublicUrl(): string
    {
        return $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost:3000/.well-known/mercure';
    }
}
