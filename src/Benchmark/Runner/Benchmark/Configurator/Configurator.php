<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark\Configurator;

use Jblairy\PhpBenchmark\Benchmark\Benchmark;
use Jblairy\PhpBenchmark\Benchmark\Exception\BenchmarkNotFound;
use Jblairy\PhpBenchmark\PhpVersion\Enum\PhpVersion;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class Configurator
{
    private ?PhpVersion $phpVersion = null;

    private ?Benchmark $benchmark = null;

    private int $iterations = 1;

    public function __construct(
        /** @var Benchmark[] */
        #[AutowireIterator(Benchmark::class)]
        private iterable $benchmarks,
    ) {
    }

    public function setPhpVersion(PhpVersion $phpVersion): void
    {
        $this->phpVersion = $phpVersion;
    }

    /**
     * @return PhpVersion[]
     */
    public function getAllPhpVersions(): array
    {
        if (null !== $this->phpVersion) {
            return [$this->phpVersion];
        }

        return PhpVersion::cases();
    }

    public function getPhpVersion(): PhpVersion
    {
        return $this->phpVersion ?? throw new RuntimeException('PHP version is not set');
    }

    public function setBenchmark(string $benchmarkName): void
    {
        if (null === $this->benchmark) {
            foreach ($this->benchmarks as $benchmark) {
                if (str_ends_with($benchmark::class, $benchmarkName)) {
                    $this->benchmark = $benchmark;
                }
            }
        }

        if (null === $this->benchmark) {
            throw new BenchmarkNotFound($benchmarkName);
        }
    }

    /**
     * @return Benchmark[]
     */
    public function getAllBenchmarks(): array
    {
        if (null !== $this->benchmark) {
            return [$this->benchmark];
        }

        return iterator_to_array($this->benchmarks);
    }

    public function getBenchmark(): Benchmark
    {
        return $this->benchmark ?? throw new RuntimeException('Benchmark is not set');
    }

    public function setIterations(int $iterations): void
    {
        $this->iterations = $iterations;
    }

    public function getIterations(): int
    {
        return $this->iterations;
    }

    public function getBenchmarkMethodBody(): string
    {
        return $this->getBenchmark()->getMethodBody($this->getPhpVersion());
    }

    public function isConfiguratedForSingleRun(): bool
    {
        return null !== $this->phpVersion && null !== $this->benchmark;
    }
}
