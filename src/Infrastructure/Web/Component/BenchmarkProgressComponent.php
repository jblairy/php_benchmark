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
        $url = $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost:3000/.well-known/mercure';
        
        return is_string($url) ? $url : 'http://localhost:3000/.well-known/mercure';
    }
}
