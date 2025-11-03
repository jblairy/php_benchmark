# ADR-001: Hexagonal Architecture (Ports & Adapters)

**Status**: Accepted  
**Date**: 2024-08-26  
**Deciders**: Development Team

## Context

PHP Benchmark is a web application for comparing PHP performance across methods and versions. The application needs to:

- Execute benchmarks in isolated Docker containers
- Store and retrieve benchmark results from a database
- Display real-time progress updates via WebSockets
- Load fixtures from YAML files
- Present data through a web interface

Without a clear architectural structure, business logic would become entangled with framework code (Symfony), database access (Doctrine), and external services (Docker, Mercure). This makes testing difficult, reduces maintainability, and creates tight coupling to specific technologies.

## Decision

We adopt **Hexagonal Architecture** (also known as Ports & Adapters) with three layers:

### 1. Domain Layer (`src/Domain/`)
- **Pure PHP business logic** with zero framework dependencies
- Contains entities, value objects, domain services, and exceptions
- Defines **Ports** (interfaces) for external interactions
- Example: `BenchmarkRepositoryPort`, `BenchmarkExecutorPort`

### 2. Application Layer (`src/Application/`)
- Orchestrates use cases and workflows
- Uses Domain services and Ports
- Contains DTOs for data transfer between layers
- Minimal dependencies (Domain only)

### 3. Infrastructure Layer (`src/Infrastructure/`)
- Implements **Adapters** for Domain Ports
- Contains framework-specific code (Symfony controllers, Doctrine entities, CLI commands)
- Handles external integrations (Docker, Mercure, filesystem)
- Example: `DoctrineBenchmarkRepository` implements `BenchmarkRepositoryPort`

### Dependency Rule
Dependencies flow **inward only**: Infrastructure → Application → Domain

```
┌─────────────────────────────────────┐
│      Infrastructure Layer           │
│  (Symfony, Doctrine, Docker, CLI)   │
│        implements Ports ↓           │
├─────────────────────────────────────┤
│      Application Layer              │
│    (Use Cases, Orchestration)       │
│        uses Domain ↓                │
├─────────────────────────────────────┤
│         Domain Layer                │
│   (Business Logic, Entities, VOs)   │
│      defines Ports ↑                │
└─────────────────────────────────────┘
```

## Consequences

### Positive
- **Testability**: Domain logic can be unit tested without mocks or database
- **Maintainability**: Business rules are isolated from technical concerns
- **Flexibility**: Easy to swap implementations (e.g., switch from Doctrine to another ORM)
- **Clear boundaries**: Developers know where to place new code
- **Framework independence**: Domain logic survives framework changes

### Negative
- **Initial complexity**: More files and abstractions than a simple MVC approach
- **Learning curve**: Team members must understand layered architecture
- **Boilerplate**: Ports and Adapters require interface definitions

### Trade-offs Accepted
- We accept increased file count for improved separation of concerns
- We accept some duplication (Domain entities + Doctrine entities) for framework independence
- We prioritize long-term maintainability over short-term development speed

## References
- [Clean Architecture by Robert C. Martin](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [Hexagonal Architecture by Alistair Cockburn](https://alistair.cockburn.us/hexagonal-architecture/)
- Project documentation: `docs/architecture/03-ports-adapters.md`
