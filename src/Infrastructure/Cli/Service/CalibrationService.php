<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Exception;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\ValueObject\CalibrationOptions;
use Jblairy\PhpBenchmark\Infrastructure\Cli\Service\ValueObject\CalibrationResult;

/**
 * Core calibration logic for benchmark iterations.
 */
final readonly class CalibrationService
{
    private const int MIN_INNER = 10;

    private const int MAX_INNER = 1000;

    /**
     * @param array<mixed> $data
     */
    public function shouldSkipBenchmark(array $data, CalibrationOptions $options): bool
    {
        if ($options->shouldSkipConfigured() && (isset($data['warmupIterations']) || isset($data['innerIterations']))) {
            return true;
        }

        $category = is_string($data['category'] ?? null) ? $data['category'] : '';

        return in_array($category, ['Iteration', 'Loop'], true);
    }

    /**
     * @param array<mixed> $benchmarkData
     */
    public function calibrateBenchmark(array $benchmarkData, float $targetTimeMs): ?CalibrationResult
    {
        $code = is_string($benchmarkData['code'] ?? null) ? $benchmarkData['code'] : '';
        $slug = is_string($benchmarkData['slug'] ?? null) ? $benchmarkData['slug'] : 'unknown';

        if ('' === $code) {
            return null;
        }

        $measuredTime = $this->measureExecutionTime($code);

        if (null === $measuredTime || 0 >= $measuredTime) {
            return null;
        }

        $optimalInner = (int) ($targetTimeMs / $measuredTime);
        $suggestedInner = max(self::MIN_INNER, min(self::MAX_INNER, $optimalInner));

        $suggestedWarmup = match (true) {
            100 < $measuredTime => 1,
            50 < $measuredTime => 3,
            10 < $measuredTime => 5,
            1 < $measuredTime => 10,
            default => 15,
        };

        $projectedTime = $measuredTime * $suggestedInner;
        $efficiency = min(100, ($projectedTime / $targetTimeMs) * 100);

        return new CalibrationResult(
            benchmark: $slug,
            measuredTime: $measuredTime,
            suggestedWarmup: $suggestedWarmup,
            suggestedInner: $suggestedInner,
            efficiency: $efficiency,
        );
    }

    private function measureExecutionTime(string $code): ?float
    {
        try {
            $script = $this->buildMeasurementScript($code);
            $tempFile = $this->createTemporaryScriptFile($script);
            $output = $this->executeMeasurementScript($tempFile);
            $this->cleanupTemporaryFile($tempFile);

            return $this->parseExecutionTimeFromOutput($output);
        } catch (Exception) {
            return null;
        }
    }

    private function buildMeasurementScript(string $code): string
    {
        return "<?php\n\n"
            . "\$start = hrtime(true);\n"
            . $code . "\n"
            . "\$end = hrtime(true);\n"
            . "\$elapsed_ms = (\$end - \$start) / 1_000_000;\n"
            . "echo json_encode(['execution_time_ms' => \$elapsed_ms]);\n";
    }

    private function createTemporaryScriptFile(string $script): string
    {
        $tempFile = sys_get_temp_dir() . '/benchmark_calibration_' . uniqid() . '.php';
        file_put_contents($tempFile, $script);

        return $tempFile;
    }

    private function executeMeasurementScript(string $tempFile): ?string
    {
        $output = shell_exec(sprintf('timeout 5s php %s 2>&1', $tempFile));

        return (null === $output || false === $output) ? null : $output;
    }

    private function cleanupTemporaryFile(string $tempFile): void
    {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    private function parseExecutionTimeFromOutput(?string $output): ?float
    {
        if (null === $output) {
            return null;
        }

        $trimmedOutput = mb_trim($output);
        if ('' === $trimmedOutput) {
            return null;
        }

        $jsonLine = $this->extractJsonFromOutput($trimmedOutput);
        if (null === $jsonLine) {
            return null;
        }

        return $this->extractExecutionTimeFromJson($jsonLine);
    }

    private function extractJsonFromOutput(string $output): ?string
    {
        $lines = explode("\n", $output);

        foreach (array_reverse($lines) as $line) {
            assert(is_string($line));
            $trimmed = mb_trim($line);

            if ('' !== $trimmed && str_starts_with($trimmed, '{')) {
                return $trimmed;
            }
        }

        return null;
    }

    private function extractExecutionTimeFromJson(string $jsonLine): ?float
    {
        $result = json_decode($jsonLine, true);

        if (!is_array($result) || !isset($result['execution_time_ms'])) {
            return null;
        }

        $executionTime = $result['execution_time_ms'];

        return is_numeric($executionTime) ? (float) $executionTime : null;
    }
}
