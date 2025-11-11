<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Execution\CodeExtraction;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\CodeExtractorPort;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Adapter\DatabaseBenchmark;

/**
 * Extracts benchmark code from database-backed benchmarks (YAML fixtures)
 * Alternative to ReflectionCodeExtractor for benchmarks loaded from database.
 */
final readonly class DatabaseCodeExtractor implements CodeExtractorPort
{
    public function __construct(
        private ReflectionCodeExtractor $reflectionCodeExtractor,
    ) {
    }

    public function extractCode(Benchmark $benchmark, PhpVersion $phpVersion): string
    {
        if ($benchmark instanceof DatabaseBenchmark) {
            return $benchmark->getMethodBody($phpVersion);
        }

        return $this->reflectionCodeExtractor->extractCode($benchmark, $phpVersion);
    }
}
