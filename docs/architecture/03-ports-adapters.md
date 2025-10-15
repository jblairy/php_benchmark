# Ports & Adapters (Hexagonal Architecture)

## Concept

**Ports** are interfaces defined in the Domain layer that specify contracts.
**Adapters** are concrete implementations in the Infrastructure layer.

This pattern allows the Domain to remain independent of technical details.

## Port/Adapter Mapping

| Port (Interface in Domain) | Adapter (Implementation in Infrastructure) | Purpose |
|----------------------------|---------------------------------------------|---------|
| `CodeExtractorPort` | `ReflectionCodeExtractor` | Extract benchmark code using PHP Reflection |
| `BenchmarkRepositoryPort` | `InMemoryBenchmarkRepository` | Find and list available benchmarks |
| `ScriptExecutorPort` | `DockerScriptExecutor` | Execute PHP scripts in Docker containers |
| `ResultPersisterPort` | `DoctrinePulseResultPersister` | Persist results to database via Doctrine |
| `BenchmarkExecutorPort` | `SingleBenchmarkExecutor` | Execute a single benchmark (Domain Service) |

## How It Works

### 1. Domain Defines the Port

```php
// src/Domain/Benchmark/Port/ScriptExecutorPort.php
namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

interface ScriptExecutorPort
{
    public function executeScript(ExecutionContext $context): BenchmarkResult;
}
```

### 2. Infrastructure Implements the Adapter

```php
// src/Infrastructure/Execution/Docker/DockerScriptExecutor.php
namespace Jblairy\PhpBenchmark\Infrastructure\Execution\Docker;

use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptExecutorPort;

final class DockerScriptExecutor implements ScriptExecutorPort
{
    public function executeScript(ExecutionContext $context): BenchmarkResult
    {
        // Technical implementation with Docker
        $tempFile = tempnam(sys_get_temp_dir(), 'benchmark_');
        file_put_contents($tempFile, $context->scriptContent);

        $command = sprintf(
            'docker-compose exec -T %s php %s',
            $context->phpVersion->value,
            $tempFile
        );

        $output = shell_exec($command);
        $metrics = json_decode($output, true);

        return new BenchmarkResult(
            executionTimeMs: $metrics['execution_time'],
            memoryUsedBytes: $metrics['memory_used'],
            memoryPeakBytes: $metrics['memory_peak'],
        );
    }
}
```

### 3. Domain Uses the Port

```php
// src/Domain/Benchmark/Service/SingleBenchmarkExecutor.php
namespace Jblairy\PhpBenchmark\Domain\Benchmark\Service;

final class SingleBenchmarkExecutor implements BenchmarkExecutorPort
{
    public function __construct(
        private readonly CodeExtractorPort $codeExtractor,
        private readonly ScriptExecutorPort $scriptExecutor,  // ← Port
    ) {}

    public function execute(BenchmarkConfiguration $configuration): BenchmarkResult
    {
        $code = $this->codeExtractor->extractCode(
            $configuration->benchmark,
            $configuration->phpVersion
        );

        $context = new ExecutionContext(
            phpVersion: $configuration->phpVersion,
            scriptContent: $code,
            benchmarkClassName: $configuration->benchmark::class,
        );

        // Domain uses Port, doesn't know about Docker
        return $this->scriptExecutor->executeScript($context);
    }
}
```

### 4. Symfony Wires Port → Adapter

```yaml
# config/services.yaml
services:
    # Port → Adapter binding
    Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptExecutorPort:
        class: Jblairy\PhpBenchmark\Infrastructure\Execution\Docker\DockerScriptExecutor

    Jblairy\PhpBenchmark\Domain\Benchmark\Port\CodeExtractorPort:
        class: Jblairy\PhpBenchmark\Infrastructure\Execution\CodeExtraction\ReflectionCodeExtractor

    Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort:
        class: Jblairy\PhpBenchmark\Infrastructure\Persistence\InMemory\InMemoryBenchmarkRepository

    Jblairy\PhpBenchmark\Domain\Benchmark\Port\ResultPersisterPort:
        class: Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\DoctrinePulseResultPersister
```

## Benefits

### 1. Domain Independence
The Domain doesn't know about Docker, Doctrine, or any technical detail.

```php
// Domain Service knows NOTHING about Docker
final class SingleBenchmarkExecutor
{
    public function __construct(
        private readonly ScriptExecutorPort $scriptExecutor,  // Interface only
    ) {}
}
```

### 2. Testability
Easy to test Domain with mock adapters:

```php
class FakeScriptExecutor implements ScriptExecutorPort
{
    public function executeScript(ExecutionContext $context): BenchmarkResult
    {
        return new BenchmarkResult(10.5, 1024, 2048);
    }
}

// Test without Docker
$executor = new SingleBenchmarkExecutor(
    $codeExtractor,
    new FakeScriptExecutor()  // Fake adapter for testing
);
```

### 3. Swappable Implementations
Want to run benchmarks via Kubernetes instead of Docker? Create a new adapter:

```php
final class KubernetesScriptExecutor implements ScriptExecutorPort
{
    public function executeScript(ExecutionContext $context): BenchmarkResult
    {
        // New implementation using Kubernetes
    }
}
```

Update `services.yaml`:
```yaml
Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptExecutorPort:
    class: Jblairy\PhpBenchmark\Infrastructure\Execution\Kubernetes\KubernetesScriptExecutor
```

**Domain code doesn't change at all!**

## Port Examples

### CodeExtractorPort
```php
interface CodeExtractorPort
{
    /**
     * Extract executable code from a benchmark for a specific PHP version
     */
    public function extractCode(Benchmark $benchmark, PhpVersion $phpVersion): string;
}
```

**Adapter:** Uses PHP Reflection to extract method body based on attributes.

### BenchmarkRepositoryPort
```php
interface BenchmarkRepositoryPort
{
    public function getAllBenchmarks(): array;
    public function findBenchmarkByName(string $name): ?Benchmark;
    public function hasBenchmark(string $name): bool;
}
```

**Adapter:** Uses Symfony's AutowireIterator to collect all Benchmark services.

### ResultPersisterPort
```php
interface ResultPersisterPort
{
    /**
     * Persist benchmark result to storage
     */
    public function persist(BenchmarkConfiguration $config, BenchmarkResult $result): void;
}
```

**Adapter:** Converts Domain models to Doctrine entities and saves to database.

## Anti-Pattern: Domain Depending on Infrastructure

❌ **Bad** (violates Clean Architecture):
```php
// Domain Service directly using Infrastructure
final class SingleBenchmarkExecutor
{
    public function execute(BenchmarkConfiguration $config): BenchmarkResult
    {
        // Domain depending on Docker directly - WRONG!
        $command = "docker-compose exec -T php84 php /tmp/benchmark.php";
        $output = shell_exec($command);
    }
}
```

✅ **Good** (using Ports):
```php
// Domain Service using Port (interface)
final class SingleBenchmarkExecutor
{
    public function __construct(
        private readonly ScriptExecutorPort $scriptExecutor,  // Port
    ) {}

    public function execute(BenchmarkConfiguration $config): BenchmarkResult
    {
        return $this->scriptExecutor->executeScript($context);
    }
}
```

## Summary

**Hexagonal Architecture = Ports & Adapters**

- **Ports**: Interfaces in Domain (what the system needs)
- **Adapters**: Implementations in Infrastructure (how it's done)
- **Benefit**: Domain stays pure, testable, and independent
- **Configuration**: Wire ports to adapters in `services.yaml`

This pattern is the **key to achieving Clean Architecture** in practice.
