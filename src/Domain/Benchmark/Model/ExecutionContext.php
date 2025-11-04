<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Model;

use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;

final readonly class ExecutionContext
{
    public function __construct(
        public PhpVersion $phpVersion,
        public string $scriptContent,
        public string $benchmarkClassName,
        public string $benchmarkSlug = 'unknown',
    ) {
    }
}
