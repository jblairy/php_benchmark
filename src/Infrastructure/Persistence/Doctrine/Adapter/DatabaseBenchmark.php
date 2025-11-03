<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Adapter;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Exception\ReflexionMethodNotFound;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity\Benchmark as BenchmarkEntity;

/**
 * Adapter that wraps a Doctrine Benchmark entity and implements Domain Benchmark contract
 * Follows Hexagonal Architecture: Infrastructure adapts to Domain interface
 */
final readonly class DatabaseBenchmark implements Benchmark
{
    public function __construct(
        private BenchmarkEntity $entity
    ) {
    }

    public function getMethodBody(PhpVersion $phpVersion): string
    {
        if (!$this->entity->supportsPhpVersion($phpVersion)) {
            throw new ReflexionMethodNotFound(
                $this->entity->getSlug(),
                $phpVersion->value
            );
        }

        return $this->entity->getCode();
    }

    public function getEntity(): BenchmarkEntity
    {
        return $this->entity;
    }

    public function getSlug(): string
    {
        return $this->entity->getSlug();
    }

    public function getName(): string
    {
        return $this->entity->getName();
    }

    public function getCategory(): string
    {
        return $this->entity->getCategory();
    }

    public function getDescription(): string
    {
        return $this->entity->getDescription();
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->entity->getTags();
    }

    public function getIcon(): ?string
    {
        return $this->entity->getIcon();
    }

    /**
     * @return PhpVersion[]
     */
    public function getSupportedPhpVersions(): array
    {
        return $this->entity->getPhpVersionEnums();
    }
}
