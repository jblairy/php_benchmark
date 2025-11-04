# 6. Create AsyncExecutorPort and EventDispatcherPort to Decouple External Libraries

Date: 2025-11-04

## Status

Accepted

## Context

The `AsyncBenchmarkRunner` use case in the Application layer was directly depending on external libraries:
- `Symfony\Contracts\EventDispatcher\EventDispatcherInterface` (Framework)
- `Spatie\Async\Pool` (Third-party library)

This violated Clean Architecture principles:
- Application layer should only depend on Domain, not Infrastructure
- PHPArkitect detected 4 architecture violations
- Code was tightly coupled to specific implementations

### Detected Violations

```
Jblairy\PhpBenchmark\Application\UseCase\AsyncBenchmarkRunner has 4 violations:
  - depends on Symfony\Contracts\EventDispatcher\EventDispatcherInterface (line 23)
  - depends on Spatie\Async\Pool (line 44)
```

## Decision

We created two new Ports (interfaces) in the Domain layer to abstract these dependencies:

### 1. EventDispatcherPort

```php
namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

interface EventDispatcherPort
{
    public function dispatch(object $event): void;
}
```

**Adapter:**
- `Infrastructure\Async\SymfonyEventDispatcherAdapter` - Adapts Symfony's EventDispatcher

### 2. AsyncExecutorPort

```php
namespace Jblairy\PhpBenchmark\Domain\Benchmark\Port;

interface AsyncExecutorPort
{
    public function addTask(callable $task, callable $onSuccess): void;
    public function wait(): void;
}
```

**Adapter:**
- `Infrastructure\Async\SpatieAsyncExecutorAdapter` - Adapts Spatie\Async\Pool

### Configuration

Both Ports are configured in `config/services.yaml` with their respective adapters:

```yaml
Jblairy\PhpBenchmark\Domain\Benchmark\Port\EventDispatcherPort:
    class: Jblairy\PhpBenchmark\Infrastructure\Async\SymfonyEventDispatcherAdapter

Jblairy\PhpBenchmark\Domain\Benchmark\Port\AsyncExecutorPort:
    class: Jblairy\PhpBenchmark\Infrastructure\Async\SpatieAsyncExecutorAdapter
    arguments:
        $concurrency: 100
```

## Consequences

### Positive

✅ **Architecture Compliance**
- PHPArkitect now passes with 0 violations
- Clean Architecture principles respected
- Application layer only depends on Domain

✅ **Testability**
- Easy to mock Ports in unit tests
- Created comprehensive test suite for `AsyncBenchmarkRunner` (5 tests, 20+ assertions)
- No need to mock external libraries

✅ **Flexibility**
- Can swap Spatie\Async for another library (ReactPHP, Amp, etc.) without touching Application layer
- Can replace Symfony EventDispatcher if needed
- Domain remains framework-agnostic

✅ **Maintainability**
- Clear separation of concerns
- Explicit dependencies through interfaces
- Better code documentation

### Negative

⚠️ **Slight Complexity Increase**
- Two additional interfaces to maintain
- Two adapter classes
- Service configuration in `services.yaml`

⚠️ **Indirection**
- One extra layer between Application and Infrastructure
- Slightly more files to navigate

### Neutral

🔵 **No Performance Impact**
- Adapters are thin wrappers
- No runtime overhead

## Implementation Details

### Files Created

1. `src/Domain/Benchmark/Port/EventDispatcherPort.php`
2. `src/Domain/Benchmark/Port/AsyncExecutorPort.php`
3. `src/Infrastructure/Async/SymfonyEventDispatcherAdapter.php`
4. `src/Infrastructure/Async/SpatieAsyncExecutorAdapter.php`
5. `tests/Unit/Application/UseCase/AsyncBenchmarkRunnerTest.php`

### Files Modified

1. `src/Application/UseCase/AsyncBenchmarkRunner.php` - Now uses Ports instead of concrete implementations
2. `config/services.yaml` - Added Port → Adapter bindings
3. `.github/workflows/quality.yml` - Added PHPArkitect check to CI

### Test Coverage

Created comprehensive test suite covering:
- BenchmarkStarted event dispatching
- Benchmark execution and result persistence
- Progress events for each iteration
- BenchmarkCompleted event after all iterations
- Async executor wait() call

**Result:** 5 tests, 20+ assertions, 100% success

## Alternatives Considered

### 1. Keep Direct Dependencies
**Rejected:** Violates Clean Architecture, harder to test, tightly coupled

### 2. Move AsyncBenchmarkRunner to Infrastructure
**Rejected:** Use cases belong in Application layer by definition

### 3. Create a Single "ExecutionPort" combining both concerns
**Rejected:** Single Responsibility Principle - event dispatching and async execution are separate concerns

## References

- [ADR 001: Hexagonal Architecture](001-hexagonal-architecture.md)
- [docs/architecture/03-ports-adapters.md](../architecture/03-ports-adapters.md)
- [PHPArkitect Configuration](../../phparkitect.php)
- [Clean Architecture by Robert C. Martin](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)

## Validation

✅ PHPStan Level Max: PASS (0 errors)
✅ PHPArkitect: PASS (0 violations)
✅ PHPUnit: PASS (46 tests, 181 assertions)
✅ PHP-CS-Fixer: PASS
