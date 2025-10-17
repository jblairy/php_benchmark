<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Contract;

use Jblairy\PhpBenchmark\Domain\Benchmark\Exception\ReflexionMethodNotFound;
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

        [$startLine, $endLine] = $this->extractMethodBodyBoundaries($reflectionMethod);
        $methodBodyLines = $this->extractLinesFromFile($fileName, $startLine, $endLine);
        $rawScript = implode('', $methodBodyLines);

        return $this->removeHeredocMarkers($rawScript);
    }

    /**
     * Extract start and end line numbers for method body content.
     * Excludes method signature (first line) and closing brace (last line).
     *
     * @return array{int, int} [startLine, endLine]
     */
    private function extractMethodBodyBoundaries(ReflectionMethod $reflectionMethod): array
    {
        $startLine = (int) $reflectionMethod->getStartLine() + 1;
        $endLine = (int) $reflectionMethod->getEndLine() - 1;

        return [$startLine, $endLine];
    }

    /**
     * Extract specific lines from a file.
     *
     * @return string[]
     */
    private function extractLinesFromFile(string $fileName, int $startLine, int $endLine): array
    {
        $code = (array) file($fileName);

        return array_slice($code, $startLine, $endLine - $startLine);
    }

    /**
     * Remove PHP heredoc syntax markers from script.
     * Cleans up '<<<PHP' opening and 'PHP;' closing markers.
     */
    private function removeHeredocMarkers(string $script): string
    {
        return str_replace(['<<<PHP', 'PHP;'], '', $script);
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
