<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Adapter;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Exception\ReflexionMethodNotFound;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity\Benchmark as BenchmarkEntity;

/**
 * Adapter that wraps a Doctrine Benchmark entity and implements Domain Benchmark contract
 * Follows Hexagonal Architecture: Infrastructure adapts to Domain interface.
 */
final readonly class DatabaseBenchmark implements Benchmark
{
    public function __construct(
        private BenchmarkEntity $benchmarkEntity,
    ) {
    }

    public function getMethodBody(PhpVersion $phpVersion): string
    {
        if (!$this->benchmarkEntity->supportsPhpVersion($phpVersion)) {
            throw new ReflexionMethodNotFound($this->benchmarkEntity->getSlug(), $phpVersion->value);
        }

        return $this->benchmarkEntity->getCode();
    }

    public function getEntity(): BenchmarkEntity
    {
        return $this->benchmarkEntity;
    }

    public function getSlug(): string
    {
        return $this->benchmarkEntity->getSlug();
    }

    public function getName(): string
    {
        return $this->benchmarkEntity->getName();
    }

    public function getCategory(): string
    {
        return $this->benchmarkEntity->getCategory();
    }

    public function getDescription(): string
    {
        return $this->benchmarkEntity->getDescription();
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->benchmarkEntity->getTags();
    }

    public function getIcon(): ?string
    {
        return $this->benchmarkEntity->getIcon();
    }

    /**
     * @return PhpVersion[]
     */
    public function getSupportedPhpVersions(): array
    {
        return $this->benchmarkEntity->getPhpVersionEnums();
    }
}
