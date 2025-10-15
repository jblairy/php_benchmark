<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Execution\CodeExtraction;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\Benchmark;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\CodeExtractorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Exception\ReflexionMethodNotFound;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use ReflectionClass;
use ReflectionMethod;

final class ReflectionCodeExtractor implements CodeExtractorPort
{
    public function extractCode(Benchmark $benchmark, PhpVersion $phpVersion): string
    {
        $method = $this->findMethodForVersion($benchmark, $phpVersion);
        $rawCode = $this->extractMethodBody($method);

        return $this->cleanCode($rawCode);
    }

    private function findMethodForVersion(Benchmark $benchmark, PhpVersion $phpVersion): ReflectionMethod
    {
        $reflection = new ReflectionClass($benchmark);

        foreach ($reflection->getMethods() as $method) {
            if ($this->methodMatchesVersion($method, $phpVersion)) {
                return $method;
            }
        }

        throw new ReflexionMethodNotFound($benchmark::class, $phpVersion->value);
    }

    private function methodMatchesVersion(ReflectionMethod $method, PhpVersion $phpVersion): bool
    {
        foreach ($method->getAttributes() as $attribute) {
            $attributeName = $attribute->getName();

            if ($attributeName === All::class) {
                return true;
            }

            if (str_ends_with(mb_strtolower($attributeName), $phpVersion->value)) {
                return true;
            }
        }

        return false;
    }

    private function extractMethodBody(ReflectionMethod $method): string
    {
        $fileName = (string) $method->getFileName();
        $startLine = (int) $method->getStartLine();
        $endLine = (int) $method->getEndLine();

        $fileLines = $this->readFileLines($fileName);

        return $this->extractBodyLinesBetweenBraces($fileLines, $startLine, $endLine);
    }

    private function readFileLines(string $fileName): array
    {
        if (!file_exists($fileName)) {
            throw new \RuntimeException("File not found: {$fileName}");
        }

        $fileLines = file($fileName);
        if ($fileLines === false) {
            throw new \RuntimeException("Cannot read file: {$fileName}");
        }

        return $fileLines;
    }

    private function extractBodyLinesBetweenBraces(array $fileLines, int $startLine, int $endLine): string
    {
        $bodyLines = [];
        for ($i = $startLine; $i < $endLine - 1; ++$i) {
            if ($this->shouldSkipLine($fileLines[$i])) {
                continue;
            }
            $bodyLines[] = $fileLines[$i];
        }

        return implode('', $bodyLines);
    }

    private function shouldSkipLine(string $line): bool
    {
        $trimmed = trim($line);
        return $trimmed === '{' || $trimmed === '';
    }

    private function cleanCode(string $code): string
    {
        $cleaned = $this->removeHeredocMarkers($code);
        $cleaned = $this->unescapeHeredocVariables($cleaned);

        return trim($cleaned);
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
