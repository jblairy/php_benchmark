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
        $reflectionMethod = $this->getReflexionMethod($phpVersion);
        $fileName = (string) $reflectionMethod->getFileName();
        $startLine = (int) $reflectionMethod->getStartLine() + 1; // TODO make it better
        $endLine = (int) $reflectionMethod->getEndLine() - 1; // TODO make it better
        $code = (array) file($fileName);

        $lines = array_slice($code, $startLine, $endLine - $startLine);
        $script = implode('', $lines);

        return str_replace(['<<<PHP', 'PHP;'], '', $script); // TODO just a POC need an improvement or refactor
    }

    private function getReflexionMethod(PhpVersion $phpVersion): ReflectionMethod
    {
        $reflectionClass = new ReflectionClass($this);

        foreach ($reflectionClass->getMethods() as $method) {
            foreach ($method->getAttributes() as $attribute) {
                if (All::class === $attribute->getName() || str_ends_with(mb_strtolower($attribute->getName()), $phpVersion->value)) {
                    return $method;
                }
            }
        }

        throw new ReflexionMethodNotFound(static::class, $phpVersion->value);
    }
}
