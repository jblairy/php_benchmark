<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Component;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Twig Component for displaying real-time benchmark progress via Mercure.
 * Updated via JavaScript/Mercure events, not server-side rendering.
 */
#[AsTwigComponent('BenchmarkProgress')]
final readonly class BenchmarkProgressComponent
{
    public function __construct(
        #[Autowire(env: 'MERCURE_PUBLIC_URL')]
        private string $mercurePublicUrl,
    ) {
    }

    public function getMercurePublicUrl(): string
    {
        return $this->mercurePublicUrl;
    }
}
