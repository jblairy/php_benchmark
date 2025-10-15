# Architecture Overview

## Introduction

This project follows **Clean Architecture** principles combined with **Domain-Driven Design (DDD)** and **Hexagonal Architecture** (Ports & Adapters) patterns.

## Core Principles

### 1. Clean Architecture
Dependencies point **inward only**: Infrastructure → Application → Domain

```
┌─────────────────────────────────────────────────────────┐
│                    Infrastructure                        │
│  (Frameworks, Database, External APIs)                  │
│                                                          │
│   ┌─────────────────────────────────────────────┐      │
│   │              Application                     │      │
│   │        (Use Cases, Orchestration)           │      │
│   │                                              │      │
│   │   ┌─────────────────────────────────────┐  │      │
│   │   │           Domain                    │  │      │
│   │   │    (Business Logic, Models)        │  │      │
│   │   │                                     │  │      │
│   │   └─────────────────────────────────────┘  │      │
│   │                                              │      │
│   └─────────────────────────────────────────────┘      │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### 2. Domain-Driven Design (DDD)
- **Domain** is the heart of the application
- Business logic is isolated from technical details
- Rich domain models with behavior
- Clear ubiquitous language

### 3. Hexagonal Architecture (Ports & Adapters)
- **Ports**: Interfaces defined in Domain
- **Adapters**: Implementations in Infrastructure
- Allows swapping implementations without changing business logic

## Benefits

### Testability
Domain logic can be tested without any infrastructure:
```php
// No database, no HTTP, no frameworks needed
$executor = new SingleBenchmarkExecutor($mockExtractor, $mockBuilder, $mockExecutor);
$result = $executor->execute($config);
```

### Maintainability
- Clear separation of concerns
- Each layer has a single responsibility
- Changes in one layer don't affect others

### Flexibility
- Swap Symfony for Laravel → Only Infrastructure changes
- Switch from MySQL to MongoDB → Only Adapters change
- Add REST API → Add new controllers in Infrastructure

### Framework Independence
The Domain doesn't know about Symfony, Doctrine, or any framework. It's pure PHP.

## Project Structure

```
src/
├── Application/              # Use Cases (orchestration)
│   ├── Service/
│   └── UseCase/
│
├── Domain/                   # Business Logic (pure PHP)
│   ├── Benchmark/
│   │   ├── Contract/        # Abstractions
│   │   ├── Exception/       # Domain exceptions
│   │   ├── Model/           # Value Objects
│   │   ├── Port/            # Interfaces (Hexagonal Ports)
│   │   ├── Service/         # Domain Services
│   │   └── Test/            # 40+ benchmark implementations
│   └── PhpVersion/
│
└── Infrastructure/          # Technical details
    ├── Cli/                # Symfony Console
    ├── Execution/          # Docker, code extraction
    ├── Persistence/        # Doctrine, repositories
    └── Web/               # HTTP controllers
```

## Layer Rules

### Domain Layer
- ✅ **Can use**: Pure PHP, no external dependencies
- ❌ **Cannot use**: Symfony, Doctrine, HTTP, database
- 📦 **Contains**: Models, Ports (interfaces), Domain Services, Exceptions

### Application Layer
- ✅ **Can use**: Domain, utility libraries
- ❌ **Cannot use**: Infrastructure directly (only via Ports)
- 📦 **Contains**: Use Cases, Application Services

### Infrastructure Layer
- ✅ **Can use**: Domain, Application, all frameworks/libraries
- 📦 **Contains**: CLI, Web, Persistence, Execution adapters
- 🎯 **Implements**: Ports defined by Domain

## Validation

Architecture rules are enforced by **PHPArkitect**:

```bash
docker-compose run --rm main vendor/bin/phparkitect check
```

See [docs/architecture/04-validation.md](04-validation.md) for details.

## Next Steps

- [Layer Details](02-layers.md) - Deep dive into each layer
- [Ports & Adapters](03-ports-adapters.md) - Hexagonal architecture implementation
- [Execution Flow](04-execution-flow.md) - How a benchmark runs through layers
