# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP benchmarking framework built with Symfony 7.3 that tests performance across different PHP versions (5.6 through 8.5). It runs benchmarks in isolated Docker containers for each PHP version and stores results in a MariaDB database with a web dashboard for visualization.

**Architecture:** Clean Architecture + Domain-Driven Design (DDD) + Hexagonal Architecture (Ports & Adapters)

## Documentation Standards

### Language
**ALL documentation MUST be written in ENGLISH.**

This includes:
- ✅ Code comments
- ✅ Documentation files (README.md, CLAUDE.md, docs/)
- ✅ Commit messages
- ✅ Pull request descriptions
- ✅ Issue descriptions
- ✅ PHPDoc blocks
- ✅ Error messages (user-facing)

**Rationale:** English is the universal language for software development and ensures maximum accessibility for contributors worldwide.

### Documentation Structure
```
/
├── README.md                    # Public-facing overview and quick start
├── CLAUDE.md                    # Developer reference (this file)
├── phparkitect.php              # Architecture rules with inline documentation
└── docs/
    ├── architecture/
    │   ├── 01-overview.md
    │   ├── 02-layers.md
    │   ├── 03-ports-adapters.md
    │   └── 04-execution-flow.md
    ├── concepts/
    │   ├── clean-architecture.md
    │   ├── ddd-patterns.md
    │   └── value-objects-vs-entities.md
    └── guides/
        ├── creating-benchmarks.md
        ├── testing.md
        └── contributing.md
```

## Development Commands

### Docker Environment
```bash
make up              # Start all Docker containers
make start           # Build Docker images
```

### Running Benchmarks
```bash
# Run all benchmarks across all PHP versions
make run

# Run a specific test (e.g., Loop)
make run test=Loop

# Run with specific iterations
make run test=Loop iterations=100

# Run on specific PHP version
docker-compose run --rm main php bin/console benchmark:run --test=Loop --php-version=php84 --iterations=10
```

### Code Quality
```bash
make phpcsfixer          # Check code style (dry run)
make phpcsfixer-fix      # Fix code style issues
make phpstan             # Run static analysis
make phpmd               # Run mess detector
make quality             # Run all quality checks and fixes
```

### Testing
```bash
docker-compose run --rm main vendor/bin/phpunit
```

## Architecture

This project follows **Clean Architecture** with **Domain-Driven Design (DDD)** and **Hexagonal Architecture (Ports & Adapters)** patterns.

### 🎯 Core Principles

1. **Clean Architecture**: Dependencies point inward (Infrastructure → Application → Domain)
2. **DDD**: Business logic is in the Domain layer, isolated from technical details
3. **Hexagonal**: Domain defines Ports (interfaces), Infrastructure provides Adapters (implementations)

### 📁 Project Structure (Clean Architecture Layers)

```
src/
├── Application/              # Use Cases (orchestration)
│   ├── Service/
│   │   └── ChartBuilder.php
│   └── UseCase/
│       ├── AsyncBenchmarkRunner.php
│       └── BenchmarkOrchestrator.php
│
├── Domain/                   # Business Logic (core)
│   ├── Benchmark/
│   │   ├── Contract/         # Abstractions
│   │   │   ├── AbstractBenchmark.php
│   │   │   └── Benchmark.php (interface)
│   │   ├── Exception/        # Domain exceptions
│   │   ├── Model/            # Value Objects & Domain Models
│   │   │   ├── BenchmarkConfiguration.php
│   │   │   ├── BenchmarkResult.php
│   │   │   └── ExecutionContext.php
│   │   ├── Port/             # Interfaces (Hexagonal Ports)
│   │   │   ├── BenchmarkExecutorPort.php
│   │   │   ├── BenchmarkRepositoryPort.php
│   │   │   ├── CodeExtractorPort.php
│   │   │   ├── ResultPersisterPort.php
│   │   │   └── ScriptExecutorPort.php
│   │   ├── Service/          # Domain Services
│   │   │   └── SingleBenchmarkExecutor.php
│   │   └── Test/             # Benchmark implementations
│   │       ├── Loop.php
│   │       ├── ArrayMap/
│   │       ├── StringConcatenation/
│   │       └── ... (40+ benchmarks)
│   └── PhpVersion/
│       ├── Attribute/        # PHP version targeting (#[Php84], #[All])
│       └── Enum/
│           └── PhpVersion.php
│
└── Infrastructure/           # Technical implementations (adapters)
    ├── Cli/
    │   └── BenchmarkCommand.php
    ├── Execution/
    │   ├── CodeExtraction/
    │   │   └── ReflectionCodeExtractor.php
    │   ├── Docker/
    │   │   └── DockerScriptExecutor.php
    │   └── ScriptBuilding/
    │       └── InstrumentedScriptBuilder.php
    ├── Persistence/
    │   ├── Doctrine/
    │   │   ├── Entity/       # Doctrine entities
    │   │   │   └── Pulse.php
    │   │   ├── Repository/   # Doctrine repositories
    │   │   │   └── PulseRepository.php
    │   │   └── DoctrinePulseResultPersister.php
    │   └── InMemory/
    │       └── InMemoryBenchmarkRepository.php
    └── Web/
        └── Controller/
            └── DashboardController.php
```

### 🔄 Dependency Flow (Hexagonal Architecture)

**Port (Domain) → Adapter (Infrastructure)**

| Port (Interface in Domain) | Adapter (Implementation in Infrastructure) |
|----------------------------|---------------------------------------------|
| `CodeExtractorPort` | `ReflectionCodeExtractor` |
| `BenchmarkRepositoryPort` | `InMemoryBenchmarkRepository` |
| `ScriptExecutorPort` | `DockerScriptExecutor` |
| `ResultPersisterPort` | `DoctrinePulseResultPersister` |
| `BenchmarkExecutorPort` | `SingleBenchmarkExecutor` (Domain Service) |

**Configuration in `config/services.yaml`:**
```yaml
Jblairy\PhpBenchmark\Domain\Benchmark\Port\CodeExtractorPort:
    class: Jblairy\PhpBenchmark\Infrastructure\Execution\CodeExtraction\ReflectionCodeExtractor
```

### 🚀 Execution Flow

```
1. CLI Command (Infrastructure/Cli/BenchmarkCommand)
   ↓ Receives: php bin/console benchmark:run --test=Loop
   ↓ Parses options and calls Application layer

2. Use Case (Application/UseCase/BenchmarkOrchestrator)
   ↓ Orchestrates execution
   ↓ Creates BenchmarkConfiguration (Domain Model)
   ↓ Delegates to AsyncBenchmarkRunner

3. AsyncBenchmarkRunner (Application/UseCase)
   ↓ Uses BenchmarkExecutorPort (Domain Port)
   ↓ Runs benchmarks in parallel (Spatie\Async\Pool)

4. SingleBenchmarkExecutor (Domain/Benchmark/Service)
   ↓ Implements BenchmarkExecutorPort
   ↓ Uses CodeExtractorPort to extract code
   ↓ Uses ScriptExecutorPort to execute
   ↓ Returns BenchmarkResult (Value Object)

5. DockerScriptExecutor (Infrastructure/Execution/Docker)
   ↓ Implements ScriptExecutorPort
   ↓ Executes in Docker container via docker-compose exec
   ↓ Returns execution metrics

6. DoctrinePulseResultPersister (Infrastructure/Persistence/Doctrine)
   ↓ Implements ResultPersisterPort
   ↓ Converts BenchmarkResult (Domain) → Pulse (Doctrine Entity)
   ↓ Persists to MariaDB via Doctrine ORM
```

### 🏗️ Domain-Driven Design (DDD) Concepts

**Value Objects (Domain/Benchmark/Model/):**
- `BenchmarkConfiguration`: Immutable configuration (benchmark + PHP version + iterations)
- `BenchmarkResult`: Immutable result (execution time + memory usage)
- `ExecutionContext`: Immutable execution context

**Entities (Infrastructure/Persistence/Doctrine/Entity/):**
- `Pulse`: Doctrine entity with ID, persisted to database

**Domain Services (Domain/Benchmark/Service/):**
- `SingleBenchmarkExecutor`: Coordinates benchmark execution

**Ports (Domain/Benchmark/Port/):**
- Interfaces that define contracts for Infrastructure

**Adapters (Infrastructure/):**
- Concrete implementations of Ports

### 📝 Creating Benchmarks

Benchmarks live in `src/Domain/Benchmark/Test/` and must:

1. **Extend `AbstractBenchmark`** (implements `Benchmark` interface)
2. **Use PHP version attributes** to specify compatibility:
   - `#[All]` - Run on all PHP versions
   - `#[Php73]`, `#[Php74]`, `#[Php84]`, etc. - Run on specific versions
   - Multiple attributes can be used on different methods

**Example:**
```php
namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;

final class Loop extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $x = [];
        for ($i = 0; 100000 > $i; ++$i) {
            $x[] = $i * 2;
        }
    }
}
```

### 🐳 PHP Version System

- **PhpVersion Enum** (`src/Domain/PhpVersion/Enum/PhpVersion.php`) - Defines available PHP versions
- **Version Attributes** (`src/Domain/PhpVersion/Attribute/`) - PHP 5.6 through 8.5, plus `All.php`
- **Docker Services** (`docker-compose.yml`) - Each PHP version runs as isolated container:
  - Shared volume mount at `/srv/php_benchmark`
  - 512MB memory limit
  - 1 CPU limit
  - `tail -f /dev/null` to keep running

### 📊 Data Layer

**Domain Models (Value Objects):**
- `BenchmarkResult` - Immutable result object

**Infrastructure Entities:**
- `Pulse` (src/Infrastructure/Persistence/Doctrine/Entity/Pulse.php) - Doctrine entity for database
  - Fields: `id`, `benchId`, `name`, `phpVersion`, `executionTimeMs`, `memoryUsedBytes`, `memoryPeakByte`

**Dashboard:**
- `DashboardController` (src/Infrastructure/Web/Controller/DashboardController.php) - Web UI at `/dashboard`
  - Aggregates benchmark results by test and PHP version
  - Calculates percentiles (P50, P80, P90, P95, P99) and averages
  - Generates charts via `ChartBuilder`

## Database

The project uses MariaDB 10.11 via Docker. Doctrine ORM is configured for entity management and migrations are in `migrations/`.

## Code Standards

### Quality Tools

- **PSR-12** coding style (enforced by PHP-CS-Fixer)
- **PHPStan level 9** static analysis with strict rules
- **PHPArkitect** for architectural constraints validation
- **PHPUnit** for unit and integration tests

### Requirements

- **PHP 8.4+** (uses asymmetric visibility: `public private(set)`)
- **Symfony 7.3** framework
- **Docker** for PHP version isolation
- **MariaDB 10.11** database

### Quality Commands

```bash
# Check code style
make phpcsfixer

# Fix code style automatically
make phpcsfixer-fix

# Run static analysis
make phpstan

# Run architecture validation
docker-compose run --rm main vendor/bin/phparkitect check

# Run tests
docker-compose run --rm main vendor/bin/phpunit

# Run all quality checks
make quality
```

## Architecture Quick Reference

### Layer Rules

| Layer | Can Use | Cannot Use | Contains |
|-------|---------|------------|----------|
| **Domain** | Pure PHP only | Symfony, Doctrine, HTTP, DB | Models, Ports, Services, Exceptions |
| **Application** | Domain, utilities | Infrastructure directly | Use Cases, Application Services |
| **Infrastructure** | Domain, Application, any lib | N/A | CLI, Web, Persistence, Execution |

### Dependency Rule

**Dependencies always point INWARD**: Infrastructure → Application → Domain

### Port/Adapter Pattern

| Port (Domain Interface) | Adapter (Infrastructure Implementation) |
|------------------------|------------------------------------------|
| `CodeExtractorPort` | `ReflectionCodeExtractor` |
| `BenchmarkRepositoryPort` | `InMemoryBenchmarkRepository` |
| `ScriptExecutorPort` | `DockerScriptExecutor` |
| `ResultPersisterPort` | `DoctrinePulseResultPersister` |

### Namespaces

```
Jblairy\PhpBenchmark\
├── Domain\{Module}\{Type}
├── Application\{Type}
└── Infrastructure\{Area}\{Type}
```

## Documentation

### Comprehensive Guides

- **[docs/README.md](docs/README.md)** - Documentation index

**Architecture:**
- [Architecture Overview](docs/architecture/01-overview.md) - Core principles and benefits
- [Layer Details](docs/architecture/02-layers.md) - Deep dive into each layer
- [Ports & Adapters](docs/architecture/03-ports-adapters.md) - Hexagonal architecture

**Concepts:**
- [Value Objects vs Entities](docs/concepts/value-objects-vs-entities.md) - DDD patterns explained

**Guides:**
- [Creating Benchmarks](docs/guides/creating-benchmarks.md) - Step-by-step tutorial

### Quick Links

- **[phparkitect.php](phparkitect.php)** - Architecture rules with inline documentation
- **[README.md](README.md)** - Public-facing overview
