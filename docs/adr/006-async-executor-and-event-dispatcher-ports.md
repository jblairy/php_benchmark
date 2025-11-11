# 6. Migrate to Symfony Messenger for Async Execution

Date: 2025-11-04 (Updated: 2025-11-11)

## Status

Superseded by Symfony Messenger implementation

## Context

The application originally used custom async execution with:
- `AsyncExecutorPort` interface in Domain layer
- `SpatieAsyncExecutorAdapter` implementation using PHP processes
- Direct coupling to `Spatie\Async\Pool`

This approach had several issues:
- Complex process management
- Limited scalability
- Difficult error handling
- No built-in retry mechanism

## Decision

We migrated to Symfony Messenger, which provides:
- Built-in async message handling
- Configurable transport (Redis)
- Automatic retries
- Better error handling
- Integration with Symfony ecosystem

## Implementation

1. Created `ExecuteBenchmarkMessage` for benchmark execution requests
2. Implemented `ExecuteBenchmarkHandler` to process messages
3. Configured Redis transport for message queue
4. Set up dedicated worker containers
5. Removed `AsyncExecutorPort` and all related implementations

## Consequences

### Positive
- Better scalability with multiple workers
- Automatic retry on failure
- Built-in monitoring and debugging tools
- Cleaner separation of concerns
- Native Symfony integration

### Negative
- Additional Redis dependency
- More complex deployment (requires worker containers)

## Current Architecture

```
Application Layer
└── MessengerBenchmarkRunner
    └── dispatch(ExecuteBenchmarkMessage) → Message Queue

Infrastructure Layer  
└── Worker Process
    └── ExecuteBenchmarkHandler
        └── Uses Domain services to execute benchmarks
```

The `EventDispatcherPort` remains as it provides clean abstraction for domain events.