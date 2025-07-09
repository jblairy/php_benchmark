<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Benchmark\Runner\Benchmark\Configurator;

use Jblairy\PhpBenchmark\Benchmark\Benchmark;
use Jblairy\PhpBenchmark\Benchmark\Exception\BenchmarkNotFound;
use Jblairy\PhpBenchmark\PhpVersion\Enum\PhpVersion;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class Configurator
{
    public ?PhpVersion $phpVersion = null;
    private int $iterations = 1;

    private string $benchmarkName = '';

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
    public function getPhpVersion(): array
    {
        if (null === $this->phpVersion) {
            return PhpVersion::cases();
        }

        return [$this->phpVersion];
    }

    public function setBenchmarkName(string $benchmarkName): void
    {
        $this->benchmarkName = $benchmarkName;
    }

    /**
     * @return Benchmark[]
     */
    public function getBenchmarks(): array
    {
        if ('' !== $this->benchmarkName) {
            foreach ($this->benchmarks as $benchmark) {
                if (str_ends_with($benchmark::class, $this->benchmarkName)) {
                    return [$benchmark];
                }
            }

            throw new BenchmarkNotFound($this->benchmarkName);
        }

        return iterator_to_array($this->benchmarks);
    }

    public function setIterations(string $iterations): void
    {
        $this->iterations = (int) $iterations;
    }

    public function getIterations(): int
    {
        return $this->iterations;
    }
}
