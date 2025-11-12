<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Execution\Docker;

use Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult;
use Jblairy\PhpBenchmark\Domain\Benchmark\Model\ExecutionContext;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptExecutorPort;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DockerScriptExecutor implements ScriptExecutorPort
{
    private const string TEMP_DIR = '/app/var/tmp';

    public function __construct(
        private LoggerInterface $logger,
        #[Autowire('%benchmark.timeout%')]
        private int $benchmarkTimeout,
        #[Autowire('%app.env%')]
        private string $appEnv,
        #[Autowire('%docker.compose_project_name%')]
        private string $composeProjectName,
    ) {
    }

    public function executeScript(ExecutionContext $executionContext): BenchmarkResult
    {
        $tempFile = $this->createTempScriptFile($executionContext->scriptContent);

        $this->logger->info('Executing benchmark', [
            'benchmark_slug' => $executionContext->benchmarkSlug,
            'benchmark_class' => $executionContext->benchmarkClassName,
            'php_version' => $executionContext->phpVersion->value,
            'script_file' => $tempFile,
        ]);

        try {
            $output = $this->executeInDocker($executionContext->phpVersion->value, $tempFile);
            $result = $this->parseOutput($output);
            $this->cleanupTempFile($tempFile);

            $this->logger->info('Benchmark executed successfully', [
                'benchmark_slug' => $executionContext->benchmarkSlug,
                'php_version' => $executionContext->phpVersion->value,
            ]);

            return $result;
        } catch (RuntimeException $runtimeException) {
            $this->logger->error('Benchmark execution failed', [
                'benchmark_slug' => $executionContext->benchmarkSlug,
                'benchmark_class' => $executionContext->benchmarkClassName,
                'php_version' => $executionContext->phpVersion->value,
                'script_file' => $tempFile,
                'error' => $runtimeException->getMessage(),
                'script_preview' => mb_substr($executionContext->scriptContent, 0, 200),
            ]);

            throw $this->enrichExceptionWithContext($runtimeException, $executionContext, $tempFile);
        }
    }

    private function createTempScriptFile(string $scriptContent): string
    {
        $tempFile = sprintf(
            '%s/benchmark_script_%s.php',
            self::TEMP_DIR,
            uniqid('', true),
        );

        $fullScript = "<?php\n\n" . $scriptContent;

        if (false === file_put_contents($tempFile, $fullScript)) {
            throw new RuntimeException('Failed to create temp file: ' . $tempFile);
        }

        return $tempFile;
    }

    private function executeInDocker(string $phpVersion, string $scriptPath): string
    {
        $composeFile = $this->getComposeFile();

        $command = sprintf(
            'timeout %ds docker-compose -p %s -f %s exec -T %s php -d max_execution_time=%d %s 2>&1',
            $this->benchmarkTimeout,
            escapeshellarg($this->composeProjectName),
            escapeshellarg($composeFile),
            escapeshellarg($phpVersion),
            $this->benchmarkTimeout,
            escapeshellarg($scriptPath),
        );

        $output = [];
        $exitCode = 0;

        exec($command, $output, $exitCode);

        // Exit code 124 = timeout command timed out
        if (124 === $exitCode) {
            throw new RuntimeException(sprintf('Script execution timed out after %d seconds', $this->benchmarkTimeout));
        }

        if (0 !== $exitCode) {
            throw new RuntimeException(sprintf('Script execution failed with code %d: %s', $exitCode, implode("\n", $output)));
        }

        return implode('', $output);
    }

    private function getComposeFile(): string
    {
        return match ($this->appEnv) {
            'prod' => '/app/docker-compose.prod.yml',
            'test' => '/app/docker-compose.ci.yml',
            default => '/app/docker-compose.dev.yml',
        };
    }

    private function parseOutput(string $output): BenchmarkResult
    {
        $data = json_decode($output, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException(sprintf('Invalid JSON output: %s. Output was: %s', json_last_error_msg(), $output));
        }

        if (!is_array($data)) {
            throw new RuntimeException('Expected array from JSON decode');
        }

        $validData = $this->ensureAssociativeArrayWithStringKeys($data);

        return BenchmarkResult::fromArray($validData);
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<string, mixed>
     */
    private function ensureAssociativeArrayWithStringKeys(array $data): array
    {
        /* @var array<string, mixed> */
        return array_filter(
            $data,
            is_string(...),
            ARRAY_FILTER_USE_KEY,
        );
    }

    private function cleanupTempFile(string $tempFile): void
    {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    private function enrichExceptionWithContext(
        RuntimeException $runtimeException,
        ExecutionContext $executionContext,
        string $tempFile,
    ): RuntimeException {
        return new RuntimeException(
            $runtimeException->getMessage() . sprintf(
                ' [Benchmark Slug: %s, Class: %s, PHP Version: %s, File: %s]',
                $executionContext->benchmarkSlug,
                $executionContext->benchmarkClassName,
                $executionContext->phpVersion->value,
                $tempFile,
            ),
            (int) $runtimeException->getCode(),
            $runtimeException,
        );
    }
}
