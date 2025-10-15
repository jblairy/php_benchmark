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

This project follows **Clean Architecture + DDD + Hexagonal Architecture** (Ports & Adapters).

📖 **Full documentation**: [docs/architecture/01-overview.md](docs/architecture/01-overview.md)

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

### Code Comments and Documentation

**Principle: Self-documenting code over comments**

#### ✅ DO: Write expressive, well-named code

```php
// ✅ GOOD - Self-explanatory
final readonly class StatisticsCalculator
{
    public function calculatePercentile(array $sortedValues, int $percentile): float
    {
        $index = (int) ceil(($percentile / 100) * count($sortedValues)) - 1;
        return $sortedValues[max(0, $index)];
    }
}
```

#### ❌ DON'T: Use comments to explain what code does

```php
// ❌ BAD - Comment explains poor naming
// Calculate the p value from the arr
public function calc(array $arr, int $p): float
{
    // Get the index
    $i = (int) ceil(($p / 100) * count($arr)) - 1;
    // Return the value
    return $arr[max(0, $i)];
}
```

#### ✅ WHEN to use comments

Comments are **acceptable and encouraged** for:

1. **"Why" not "What"** - Explain business decisions or complex algorithms
   ```php
   // Using P90 instead of average to avoid outliers skewing benchmark results
   $p90 = $this->calculatePercentile($times, 90);
   ```

2. **API documentation** - Public interfaces and contracts (PHPDoc)
   ```php
   /**
    * Port for accessing dashboard data
    */
   interface DashboardRepositoryPort
   {
       /**
        * @return BenchmarkMetrics[] Grouped by benchmark ID and PHP version
        */
       public function getAllBenchmarkMetrics(): array;
   }
   ```

3. **Class-level documentation** - Purpose and responsibility
   ```php
   /**
    * Doctrine adapter implementing DashboardRepositoryPort
    *
    * Follows Dependency Inversion Principle: implements interface from Domain
    */
   final readonly class DoctrineDashboardRepository implements DashboardRepositoryPort
   ```

4. **Non-obvious workarounds** - Technical constraints or edge cases
   ```php
   // PHP 5.6 doesn't support ** operator, must use pow()
   if ($phpVersion === 'php56') {
       return pow($base, $exponent);
   }
   ```

#### ❌ AVOID these comments

- **Obvious comments**: `// Set the name` above `$this->name = $name;`
- **Commented-out code**: Delete it (use git history if needed)
- **Redundant documentation**: Repeating method signature in PHPDoc
  ```php
  // ❌ BAD
  /**
   * Get dashboard data
   * @return DashboardData The dashboard data
   */
  public function getDashboardData(): DashboardData
  ```

- **TODO/FIXME without context**: Always add reason and date
  ```php
  // ❌ BAD: TODO fix this
  // ✅ GOOD: TODO (2024-10-15): Refactor to use async for performance
  ```

#### Best Practices

1. **Prefer refactoring over commenting** - If code needs a comment to be understood, consider refactoring
2. **Use meaningful names** - `calculatePercentile()` > `calc()` + comment
3. **Extract complex logic** - Into well-named private methods or Value Objects
4. **Keep comments up-to-date** - Outdated comments are worse than no comments

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
