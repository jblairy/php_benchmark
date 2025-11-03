# Architecture Layers

## Domain Layer

### Purpose
The **Domain Layer** is the heart of the application. It contains pure business logic with no external dependencies.

### Structure
```
src/Domain/
├── Benchmark/
│   ├── Event/                 # Domain Events
│   │   ├── BenchmarkStarted.php
│   │   ├── BenchmarkProgress.php
│   │   └── BenchmarkCompleted.php
│   │
│   ├── Exception/             # Domain-specific exceptions
│   │   ├── BenchmarkNotFound.php
│   │   └── ReflexionMethodNotFound.php
│   │
│   ├── Model/                 # Value Objects & Domain Models
│   │   ├── BenchmarkConfiguration.php
│   │   ├── BenchmarkResult.php
│   │   └── ExecutionContext.php
│   │
│   ├── Port/                  # Interfaces (Hexagonal Ports)
│   │   ├── BenchmarkExecutorPort.php
│   │   ├── BenchmarkRepositoryPort.php
│   │   ├── CodeExtractorPort.php
│   │   ├── ResultPersisterPort.php
│   │   └── ScriptExecutorPort.php
│   │
│   └── Service/               # Domain Services
│       └── SingleBenchmarkExecutor.php
│
├── Dashboard/
│   ├── Model/                 # Dashboard Value Objects
│   │   ├── BenchmarkMetrics.php
│   │   ├── BenchmarkStatistics.php
│   │   └── PercentileMetrics.php
│   │
│   ├── Port/                  # Dashboard Ports
│   │   └── DashboardRepositoryPort.php
│   │
│   └── Service/               # Dashboard Services
│       └── StatisticsCalculator.php
│
└── PhpVersion/
    ├── Attribute/             # PHP version targeting
    │   ├── All.php
    │   ├── Php80.php
    │   └── ...
    └── Enum/
        └── PhpVersion.php

Note: Benchmarks are now stored in database via YAML fixtures (fixtures/benchmarks/*.yaml)
      and loaded by Infrastructure layer (see Persistence section)
```

### Namespace Pattern
```
Jblairy\PhpBenchmark\Domain\{Module}\{Type}
```

Examples:
- `Jblairy\PhpBenchmark\Domain\Benchmark\Model\BenchmarkResult`
- `Jblairy\PhpBenchmark\Domain\Benchmark\Port\CodeExtractorPort`
- `Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion`

### Rules
- ✅ Only pure PHP code
- ❌ No framework dependencies (Symfony, Doctrine, etc.)
- ❌ No infrastructure concerns (HTTP, database, files)
- ✅ Rich domain models with behavior
- ✅ Immutable value objects (using `readonly`)

### Examples

**Value Object:**
```php
final readonly class BenchmarkResult
{
    public function __construct(
        public float $executionTimeMs,
        public float $memoryUsedBytes,
        public float $memoryPeakBytes,
    ) {}
}
```

**Port (Interface):**
```php
interface ResultPersisterPort
{
    public function persist(BenchmarkConfiguration $config, BenchmarkResult $result): void;
}
```

---

## Application Layer

### Purpose
The **Application Layer** orchestrates domain logic. It defines **what the application does** (use cases).

### Structure
```
src/Application/
├── Service/
│   └── ChartBuilder.php       # Application service
│
└── UseCase/
    ├── AsyncBenchmarkRunner.php
    └── BenchmarkOrchestrator.php
```

### Namespace Pattern
```
Jblairy\PhpBenchmark\Application\{Type}
```

Examples:
- `Jblairy\PhpBenchmark\Application\UseCase\BenchmarkOrchestrator`
- `Jblairy\PhpBenchmark\Application\Service\ChartBuilder`

### Rules
- ✅ Can use Domain layer
- ❌ Cannot use Infrastructure directly
- ✅ Uses Ports (interfaces) from Domain
- ✅ Orchestrates multiple domain services
- ❌ No framework-specific code

### Example

```php
final class BenchmarkOrchestrator
{
    public function __construct(
        private readonly AsyncBenchmarkRunner $runner,
        private readonly CodeExtractorPort $codeExtractor, // Port from Domain
    ) {}

    public function executeSingle(BenchmarkConfiguration $configuration): void
    {
        // Orchestrates use case
        $this->runner->run($configuration);
    }

    public function executeMultiple(array $benchmarks, array $phpVersions, int $iterations): void
    {
        foreach ($benchmarks as $benchmark) {
            foreach ($phpVersions as $phpVersion) {
                if (!$this->benchmarkSupportsVersion($benchmark, $phpVersion)) {
                    continue;
                }

                $config = new BenchmarkConfiguration(
                    benchmark: $benchmark,
                    phpVersion: $phpVersion,
                    iterations: $iterations,
                );

                $this->runner->run($config);
            }
        }
    }
}
```

---

## Infrastructure Layer

### Purpose
The **Infrastructure Layer** contains all technical implementations: frameworks, databases, external services, etc.

### Structure
```
src/Infrastructure/
├── Cli/                       # Command Line Interface
│   └── Command/
│       └── BenchmarkCommand.php
│
├── Execution/                 # Benchmark execution
│   ├── CodeExtraction/
│   │   └── ReflectionCodeExtractor.php
│   ├── Docker/
│   │   └── DockerScriptExecutor.php
│   └── ScriptBuilding/
│       └── InstrumentedScriptBuilder.php
│
├── Mercure/                   # Real-time events
│   └── EventSubscriber/
│       └── BenchmarkProgressSubscriber.php
│
├── Persistence/               # Data persistence
│   ├── Doctrine/
│   │   ├── Entity/
│   │   │   ├── Benchmark.php        # Benchmark definitions (from YAML)
│   │   │   └── Pulse.php            # Execution results
│   │   ├── Fixtures/
│   │   │   └── YamlBenchmarkFixtures.php   # Loads benchmarks from YAML
│   │   ├── Repository/
│   │   │   ├── DoctrineDashboardRepository.php
│   │   │   └── PulseRepository.php
│   │   └── DoctrinePulseResultPersister.php
│   └── InMemory/
│       └── InMemoryBenchmarkRepository.php
│
└── Web/                       # HTTP layer
    ├── Component/
    │   └── BenchmarkProgressComponent.php  # Live Component
    ├── Controller/
    │   └── DashboardController.php
    └── Presentation/
        └── ChartBuilder.php
```

### Namespace Pattern
```
Jblairy\PhpBenchmark\Infrastructure\{Area}\{Type}
```

Examples:
- `Jblairy\PhpBenchmark\Infrastructure\Cli\BenchmarkCommand`
- `Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity\Pulse`
- `Jblairy\PhpBenchmark\Infrastructure\Execution\Docker\DockerScriptExecutor`

### Rules
- ✅ Can use Domain and Application layers
- ✅ Can use any framework/library
- ✅ Implements Ports from Domain
- ✅ Contains all technical details

### Examples

**Adapter (implements Port):**
```php
final class DockerScriptExecutor implements ScriptExecutorPort
{
    public function executeScript(ExecutionContext $context): BenchmarkResult
    {
        // Technical implementation using Docker
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

**CLI Command:**
```php
#[AsCommand(name: 'benchmark:run')]
final class BenchmarkCommand extends Command
{
    public function __construct(
        private readonly BenchmarkOrchestrator $orchestrator,
        private readonly BenchmarkRepositoryPort $registry,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $testName = $input->getOption('test');
        $iterations = (int) $input->getOption('iterations');

        if ($testName !== null) {
            $benchmark = $this->registry->findBenchmarkByName($testName);
            $config = new BenchmarkConfiguration($benchmark, PhpVersion::PHP_8_4, $iterations);
            $this->orchestrator->executeSingle($config);
        } else {
            $this->orchestrator->executeAll($this->registry, $iterations);
        }

        return Command::SUCCESS;
    }
}
```

---

## Summary

| Layer | Responsibilities | Dependencies | Example Classes |
|-------|-----------------|--------------|-----------------|
| **Domain** | Business logic | None (pure PHP) | `BenchmarkResult`, `CodeExtractorPort` |
| **Application** | Use case orchestration | Domain only | `BenchmarkOrchestrator`, `AsyncBenchmarkRunner` |
| **Infrastructure** | Technical implementation | Domain, Application, any lib | `DockerScriptExecutor`, `BenchmarkCommand` |

**Key Rule:** Dependencies always point **inward**: Infrastructure → Application → Domain
