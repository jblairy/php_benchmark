<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Contract;

use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(Benchmark::class)]
interface Benchmark
{
    public function getMethodBody(PhpVersion $phpVersion): string;
}
