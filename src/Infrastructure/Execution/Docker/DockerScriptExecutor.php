<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Execution\Docker;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\ExecutionContext;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptExecutorPort;

final class DockerScriptExecutor implements ScriptExecutorPort
{
    private const TEMP_DIR = '/srv/php_benchmark/var/tmp';

    public function executeScript(ExecutionContext $context): BenchmarkResult
    {
        $tempFile = $this->createTempScriptFile($context->scriptContent);

        try {
            $output = $this->executeInDocker($context->phpVersion->value, $tempFile);
            $result = $this->parseOutput($output);
            $this->cleanupTempFile($tempFile);
            return $result;
        } catch (\RuntimeException $e) {
            throw $this->enrichExceptionWithContext($e, $context, $tempFile);
        }
    }

    private function createTempScriptFile(string $scriptContent): string
    {
        $tempFile = sprintf(
            '%s/benchmark_script_%s.php',
            self::TEMP_DIR,
            uniqid('', true)
        );

        $fullScript = "<?php\n\n" . $scriptContent;

        if (file_put_contents($tempFile, $fullScript) === false) {
            throw new \RuntimeException("Failed to create temp file: {$tempFile}");
        }

        return $tempFile;
    }

    private function executeInDocker(string $phpVersion, string $scriptPath): string
    {
        $command = sprintf(
            'docker-compose exec -T %s php %s 2>&1',
            escapeshellarg($phpVersion),
            escapeshellarg($scriptPath)
        );

        $output = [];
        $exitCode = 0;

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException(
                sprintf('Script execution failed with code %d: %s', $exitCode, implode("\n", $output))
            );
        }

        return implode('', $output);
    }

    private function parseOutput(string $output): BenchmarkResult
    {
        $data = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                sprintf('Invalid JSON output: %s. Output was: %s', json_last_error_msg(), $output)
            );
        }

        if (!is_array($data)) {
            throw new \RuntimeException('Expected array from JSON decode');
        }

        return BenchmarkResult::fromArray($data);
    }

    private function cleanupTempFile(string $tempFile): void
    {
        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }
    }

    private function enrichExceptionWithContext(
        \RuntimeException $exception,
        ExecutionContext $context,
        string $tempFile
    ): \RuntimeException {
        return new \RuntimeException(
            $exception->getMessage() . sprintf(' [Benchmark: %s, File: %s]', $context->benchmarkClassName, $tempFile),
            $exception->getCode(),
            $exception
        );
    }
}
