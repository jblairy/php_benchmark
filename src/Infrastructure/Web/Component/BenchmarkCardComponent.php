<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Component;

use Jblairy\PhpBenchmark\Application\Dashboard\DTO\BenchmarkData;
use Jblairy\PhpBenchmark\Application\Dashboard\UseCase\GetBenchmarkStatistics;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use Jblairy\PhpBenchmark\Infrastructure\Web\Presentation\ChartBuilder;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('BenchmarkCard')]
final class BenchmarkCardComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $benchmarkId = '';

    #[LiveProp]
    public string $benchmarkName = '';

    private ?BenchmarkData $data = null;
    private ?Chart $chart = null;

    public function __construct(
        private readonly GetBenchmarkStatistics $getBenchmarkStatistics,
        private readonly ChartBuilder $chartBuilder,
    ) {
    }

    public function getData(): ?BenchmarkData
    {
        if (null === $this->data && '' !== $this->benchmarkId && '' !== $this->benchmarkName) {
            $this->data = $this->getBenchmarkStatistics->execute($this->benchmarkId, $this->benchmarkName);
        }

        return $this->data;
    }

    public function getChart(): ?Chart
    {
        if (null === $this->chart && null !== $this->getData()) {
            $this->chart = $this->chartBuilder->createBenchmarkChart(
                $this->getData(),
                $this->getAllPhpVersions(),
            );
        }

        return $this->chart;
    }

    /**
     * @return string[]
     */
    private function getAllPhpVersions(): array
    {
        return array_map(fn (PhpVersion $version): string => $version->value, PhpVersion::cases());
    }

    public function getCategory(): string
    {
        if ('' === $this->benchmarkName) {
            return '';
        }

        $parts = explode('\\', $this->benchmarkName);

        // Return the second-to-last part (before the class name)
        return count($parts) >= 2 ? $parts[count($parts) - 2] : '';
    }

    public function getShortName(): string
    {
        if ('' === $this->benchmarkName) {
            return $this->benchmarkId;
        }

        $parts = explode('\\', $this->benchmarkName);

        // Return the last part (class name)
        return end($parts) ?: $this->benchmarkId;
    }

    public function getSourceCode(): string
    {
        if ('' === $this->benchmarkName) {
            return '';
        }

        try {
            $reflection = new \ReflectionClass($this->benchmarkName);
            $method = $reflection->getMethod('execute');
            $filename = $method->getFileName();
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();

            if (false === $filename || false === $startLine || false === $endLine) {
                return '';
            }

            $lines = file($filename);
            if (false === $lines) {
                return '';
            }

            // Extract method code (including signature)
            $code = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

            // Remove method signature and braces to show only the body
            $code = preg_replace('/^\s*public\s+function\s+execute\([^)]*\)\s*:\s*\w+\s*\{/', '', $code);
            $code = preg_replace('/\}\s*$/', '', $code);

            return trim($code);
        } catch (\ReflectionException $e) {
            return '';
        }
    }
}
