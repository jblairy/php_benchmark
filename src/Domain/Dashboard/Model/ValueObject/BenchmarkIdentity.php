<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Dashboard\Model\ValueObject;

/**
 * Value Object representing benchmark identity information.
 */
final readonly class BenchmarkIdentity
{
    public function __construct(
        public string $benchmarkId,
        public string $benchmarkName,
        public string $phpVersion,
    ) {
    }

    public static function create(string $benchmarkId, string $benchmarkName, string $phpVersion): self
    {
        return new self($benchmarkId, $benchmarkName, $phpVersion);
    }
}
