<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Contract;

use Jblairy\PhpBenchmark\Benchmark\Exception\ReflexionMethodNotFound;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use ReflectionClass;
use ReflectionMethod;

abstract class AbstractBenchmark implements Benchmark
{
    public function getMethodBody(PhpVersion $phpVersion): string
    {
        $reflection = $this->getReflexionMethod($phpVersion);
        $fileName = (string) $reflection->getFileName();
        $startLine = (int) $reflection->getStartLine() + 1; // TODO make it better
        $endLine = (int) $reflection->getEndLine() - 1; // TODO make it better
        $code = (array) file($fileName);

        $lines = array_slice($code, $startLine, $endLine - $startLine);
        $script = implode('', $lines);

        return str_replace(['<<<PHP', 'PHP;'], '', $script); // TODO just a POC need an improvement or refactor
    }

    private function getReflexionMethod(PhpVersion $phpVersion): ReflectionMethod
    {
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getAttributes() as $attribute) {
                if (All::class === $attribute->getName() || str_ends_with(mb_strtolower($attribute->getName()), $phpVersion->value)) {
                    return $method;
                }
            }
        }

        throw new ReflexionMethodNotFound($this::class, $phpVersion->value);
    }
}
