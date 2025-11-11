<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Execution\CodeExtraction;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Exception\ReflexionMethodNotFound;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\CodeExtractorPort;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

final class ReflectionCodeExtractor implements CodeExtractorPort
{
    public function extractCode(Benchmark $benchmark, PhpVersion $phpVersion): string
    {
        $reflectionMethod = $this->findMethodForVersion($benchmark, $phpVersion);
        $rawCode = $this->extractMethodBody($reflectionMethod);

        return $this->cleanCode($rawCode);
    }

    private function findMethodForVersion(Benchmark $benchmark, PhpVersion $phpVersion): ReflectionMethod
    {
        $reflectionClass = new ReflectionClass($benchmark);

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            if ($this->methodMatchesVersion($reflectionMethod, $phpVersion)) {
                return $reflectionMethod;
            }
        }

        throw new ReflexionMethodNotFound($benchmark::class, $phpVersion->value);
    }

    private function methodMatchesVersion(ReflectionMethod $reflectionMethod, PhpVersion $phpVersion): bool
    {
        foreach ($reflectionMethod->getAttributes() as $attribute) {
            $attributeName = $attribute->getName();

            if (All::class === $attributeName) {
                return true;
            }

            if (str_ends_with(mb_strtolower($attributeName), $phpVersion->value)) {
                return true;
            }
        }

        return false;
    }

    private function extractMethodBody(ReflectionMethod $reflectionMethod): string
    {
        $fileName = (string) $reflectionMethod->getFileName();
        $startLine = (int) $reflectionMethod->getStartLine();
        $endLine = (int) $reflectionMethod->getEndLine();

        $fileLines = $this->readFileLines($fileName);

        return $this->extractBodyLinesBetweenBraces($fileLines, $startLine, $endLine);
    }

    /**
     * @return string[]
     */
    private function readFileLines(string $fileName): array
    {
        if (!file_exists($fileName)) {
            throw new RuntimeException('File not found: ' . $fileName);
        }

        $fileLines = file($fileName);
        if (false === $fileLines) {
            throw new RuntimeException('Cannot read file: ' . $fileName);
        }

        return $fileLines;
    }

    /**
     * @param string[] $fileLines
     */
    private function extractBodyLinesBetweenBraces(array $fileLines, int $startLine, int $endLine): string
    {
        $bodyLines = [];
        for ($i = $startLine; $i < $endLine - 1; ++$i) {
            if (!isset($fileLines[$i])) {
                continue;
            }

            if ($this->shouldSkipLine($fileLines[$i])) {
                continue;
            }

            $bodyLines[] = $fileLines[$i];
        }

        return implode('', $bodyLines);
    }

    private function shouldSkipLine(string $line): bool
    {
        $trimmed = mb_trim($line);

        return '{' === $trimmed || '' === $trimmed;
    }

    private function cleanCode(string $code): string
    {
        $cleaned = $this->removeHeredocMarkers($code);
        $cleaned = $this->unescapeHeredocVariables($cleaned);

        return mb_trim($cleaned);
    }

    private function removeHeredocMarkers(string $code): string
    {
        return str_replace(['<<<PHP', 'PHP;'], '', $code);
    }

    private function unescapeHeredocVariables(string $code): string
    {
        return str_replace('\\$', '$', $code);
    }
}
