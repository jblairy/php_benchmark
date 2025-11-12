<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Resolves benchmark fixture files based on CLI options.
 */
final readonly class BenchmarkFileResolver
{
    public function __construct(private string $projectDir)
    {
    }

    /**
     * @return list<string>|null
     */
    public function resolveBenchmarkFiles(
        ?string $benchmark,
        bool $all,
        SymfonyStyle $symfonyStyle,
    ): ?array {
        $fixturesPath = $this->projectDir . '/fixtures/benchmarks';

        if (!is_dir($fixturesPath)) {
            $symfonyStyle->error('Fixtures directory not found: ' . $fixturesPath);

            return null;
        }

        if ($all) {
            return $this->getAllBenchmarkFiles($fixturesPath, $symfonyStyle);
        }

        return $this->getSingleBenchmarkFile($benchmark, $fixturesPath, $symfonyStyle);
    }

    /**
     * @return list<string>|null
     */
    private function getAllBenchmarkFiles(string $fixturesPath, SymfonyStyle $symfonyStyle): ?array
    {
        $globResult = glob($fixturesPath . '/*.yaml');
        if (false === $globResult) {
            $symfonyStyle->error('Failed to read fixtures directory');

            return null;
        }

        $symfonyStyle->info(sprintf('Calibrating %d benchmarks...', count($globResult)));

        return $globResult;
    }

    /**
     * @return list<string>|null
     */
    private function getSingleBenchmarkFile(
        ?string $benchmark,
        string $fixturesPath,
        SymfonyStyle $symfonyStyle,
    ): ?array {
        if (!is_string($benchmark)) {
            $symfonyStyle->error('Please specify --benchmark=<slug> or --all');

            return null;
        }

        $file = $fixturesPath . '/' . $benchmark . '.yaml';
        if (!file_exists($file)) {
            $symfonyStyle->error(sprintf('Benchmark not found: %s', $benchmark));

            return null;
        }

        return [$file];
    }
}
