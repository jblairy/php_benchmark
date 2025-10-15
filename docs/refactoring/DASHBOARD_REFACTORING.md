# Dashboard Refactoring - Clean Architecture + SOLID

**Date:** October 15, 2024

## Summary

Complete refactoring of `DashboardController` and its dependencies applying Clean Architecture, Domain-Driven Design, and SOLID principles.

## Violations Fixed

### Before Refactoring

❌ **DashboardController (110 lines)**:
- **SRP violation**: Controller does data fetching, grouping, statistics calculation, and chart building
- **Clean Architecture violation**: Infrastructure depends directly on Doctrine EntityManager and Entity
- **DIP violation**: No abstractions (Ports) for data access
- **God Method**: `dashboard()` method with 97 lines
- **Business logic in Infrastructure**: Percentile calculation, data grouping

❌ **ChartBuilder**:
- **Wrong layer**: In Application but should be Infrastructure (presentation concern)
- **Framework leak**: Depends on Symfony UX Chartjs in Application layer

### Metrics

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| **DashboardController** | 110 lines | 51 lines | **-54%** |
| **Responsibilities** | 5 concerns | 1 concern (HTTP) | **SRP ✓** |
| **Business logic** | In controller | In Domain | **Clean Arch ✓** |
| **Dependencies** | Doctrine direct | Via Ports | **DIP ✓** |
| **Testability** | Hard (needs DB) | Easy (mockable) | **✓** |

## New Architecture

### Domain Layer (Business Logic)

```
src/Domain/Dashboard/
├── Model/                              # Value Objects
│   ├── BenchmarkMetrics.php           # Raw metrics (before analysis)
│   ├── BenchmarkStatistics.php        # Calculated statistics
│   └── PercentileMetrics.php          # P50, P80, P90, P95, P99
│
├── Service/
│   └── StatisticsCalculator.php       # Percentile & average calculations
│
└── Port/
    └── DashboardRepositoryPort.php    # Interface for data access
```

**Key Points:**
- ✅ Pure PHP, no framework dependencies
- ✅ Immutable Value Objects (`readonly`)
- ✅ Single Responsibility: StatisticsCalculator only calculates
- ✅ Domain defines Port (interface)

### Application Layer (Use Cases)

```
src/Application/Dashboard/
├── DTO/                                # Data Transfer Objects
│   ├── BenchmarkStatisticsData.php    # Statistics for one PHP version
│   ├── BenchmarkGroup.php             # Statistics grouped by benchmark
│   └── DashboardData.php              # Complete dashboard data
│
└── UseCase/
    └── GetDashboardStatistics.php     # Orchestrates dashboard data retrieval
```

**Key Points:**
- ✅ Orchestrates domain logic
- ✅ Uses Ports from Domain
- ✅ Returns DTOs for presentation layer
- ✅ No framework dependencies

### Infrastructure Layer (Technical Details)

```
src/Infrastructure/
├── Persistence/Doctrine/Repository/
│   └── DoctrineDashboardRepository.php    # Implements DashboardRepositoryPort
│
└── Web/
    ├── Controller/
    │   └── DashboardController.php         # Refactored (51 lines)
    └── Presentation/
        └── ChartBuilder.php                # Moved from Application
```

**Key Points:**
- ✅ Repository implements Port from Domain
- ✅ Controller only handles HTTP (SRP)
- ✅ ChartBuilder in correct layer (Infrastructure)
- ✅ Depends on Domain via abstractions (DIP)

## SOLID Principles Applied

### 1. Single Responsibility Principle (SRP)

**Before:**
```php
class DashboardController {
    public function dashboard() {
        // Fetch data
        // Group data
        // Calculate statistics
        // Build charts
        // Render view
    }
}
```

**After:**
```php
// Each class has ONE responsibility

class StatisticsCalculator {
    // Only calculates statistics
}

class DoctrineDashboardRepository {
    // Only fetches data
}

class GetDashboardStatistics {
    // Only orchestrates
}

class DashboardController {
    // Only handles HTTP
}

class ChartBuilder {
    // Only builds charts
}
```

### 2. Open/Closed Principle (OCP)

**Extensibility:**
```php
// Want a different calculation algorithm?
// → Create new StatisticsCalculator implementation

// Want data from MongoDB instead of MySQL?
// → Create MongoDashboardRepository implementing DashboardRepositoryPort

// Want different chart library?
// → Create new ChartBuilder

// Domain code doesn't change!
```

### 3. Liskov Substitution Principle (LSP)

```php
// Any implementation of DashboardRepositoryPort works
interface DashboardRepositoryPort {
    public function getAllBenchmarkMetrics(): array;
}

// Can substitute without breaking
$repository = new DoctrineDashboardRepository();
$repository = new MongoDashboardRepository();
$repository = new InMemoryDashboardRepository(); // For tests
```

### 4. Interface Segregation Principle (ISP)

```php
// Specific interfaces, not monolithic
interface DashboardRepositoryPort {
    public function getAllBenchmarkMetrics(): array;
    public function getAllPhpVersions(): array;
}

// Not a giant "RepositoryInterface" with 50 methods
```

### 5. Dependency Inversion Principle (DIP)

**Before:**
```php
class DashboardController {
    public function dashboard(EntityManagerInterface $em) {
        // Depends on Doctrine (concrete)
    }
}
```

**After:**
```php
class GetDashboardStatistics {
    public function __construct(
        private DashboardRepositoryPort $repository  // Depends on abstraction
    ) {}
}

// services.yaml wires Port → Adapter
DashboardRepositoryPort:
    class: DoctrineDashboardRepository
```

## Clean Architecture Benefits

### 1. Testability

**Before:**
```php
// Hard to test - needs Doctrine, Database, EntityManager mock
$controller->dashboard($entityManager, $chartBuilder);
```

**After:**
```php
// Easy to test - pure PHP, mockable dependencies
$mockRepository = new InMemoryDashboardRepository();
$calculator = new StatisticsCalculator();
$useCase = new GetDashboardStatistics($mockRepository, $calculator);

// Test without database!
$result = $useCase->execute();
```

### 2. Independence

**Domain doesn't know about:**
- ❌ Symfony
- ❌ Doctrine
- ❌ HTTP
- ❌ Database
- ❌ Charts

**Result:** Can test, reuse, and change infrastructure without touching business logic.

### 3. Maintainability

```
Before: 110-line God Method
After:  Multiple small, focused classes (5-20 lines each)

Before: All logic mixed together
After:  Clear separation by responsibility

Before: Hard to understand
After:  Self-documenting code
```

## Migration Guide

### Step 1: Delete Old Files

```bash
# Old files backed up with .old extension
rm src/Infrastructure/Web/Controller/DashboardController.php.old
rm src/Application/Service/ChartBuilder.php
```

### Step 2: Clear Cache

```bash
docker-compose run --rm main php bin/console cache:clear
```

### Step 3: Test Dashboard

```bash
# Visit http://localhost/dashboard
# Should work identically to before
```

## Code Examples

### Domain Service (Pure Business Logic)

```php
final readonly class StatisticsCalculator
{
    public function calculate(BenchmarkMetrics $metrics): BenchmarkStatistics
    {
        if ($metrics->isEmpty()) {
            return $this->createEmptyStatistics($metrics);
        }

        $sortedTimes = $metrics->executionTimes;
        sort($sortedTimes);

        $percentiles = new PercentileMetrics(
            p50: $this->calculatePercentile($sortedTimes, 50),
            p80: $this->calculatePercentile($sortedTimes, 80),
            p90: $this->calculatePercentile($sortedTimes, 90),
            p95: $this->calculatePercentile($sortedTimes, 95),
            p99: $this->calculatePercentile($sortedTimes, 99),
        );

        return new BenchmarkStatistics(
            benchmarkId: $metrics->benchmarkId,
            benchmarkName: $metrics->benchmarkName,
            phpVersion: $metrics->phpVersion,
            executionCount: $metrics->getExecutionCount(),
            averageExecutionTime: $this->calculateAverage($metrics->executionTimes),
            percentiles: $percentiles,
            averageMemoryUsed: $this->calculateAverage($metrics->memoryUsages),
            peakMemoryUsed: $this->calculateMax($metrics->memoryPeaks),
        );
    }
}
```

### Use Case (Orchestration)

```php
final readonly class GetDashboardStatistics
{
    public function __construct(
        private DashboardRepositoryPort $repository,
        private StatisticsCalculator $statisticsCalculator,
    ) {}

    public function execute(): DashboardData
    {
        $allMetrics = $this->repository->getAllBenchmarkMetrics();
        $benchmarkGroups = $this->groupStatisticsByBenchmark($allMetrics);
        $allPhpVersions = $this->repository->getAllPhpVersions();

        return new DashboardData($benchmarkGroups, $allPhpVersions);
    }
}
```

### Controller (Minimal)

```php
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly GetDashboardStatistics $getDashboardStatistics,
        private readonly ChartBuilder $chartBuilder,
    ) {}

    #[Route('/dashboard')]
    public function dashboard(): Response
    {
        $dashboardData = $this->getDashboardStatistics->execute();

        $benchmarkStats = array_map(
            fn($group) => $this->addChart($group, $dashboardData->allPhpVersions),
            $dashboardData->benchmarks
        );

        return $this->render('dashboard/index.html.twig', [
            'stats' => $benchmarkStats,
            'allPhpVersions' => $dashboardData->allPhpVersions,
        ]);
    }
}
```

## Validation with PHPArkitect

The refactoring respects architectural rules:

```bash
docker-compose run --rm main vendor/bin/phparkitect check
```

✅ **No violations:**
- Domain doesn't depend on Infrastructure
- Application doesn't depend on Infrastructure
- Ports defined in Domain, Adapters in Infrastructure

## Next Steps

### For Future Development

1. **Add more statistics**: Create new methods in `StatisticsCalculator`
2. **Change database**: Implement new adapter for `DashboardRepositoryPort`
3. **Add caching**: Create decorator implementing `DashboardRepositoryPort`
4. **Unit tests**: Easy to test with mock repository

### Recommended Tests

```php
// Test StatisticsCalculator (pure unit test)
class StatisticsCalculatorTest extends TestCase
{
    public function testCalculatePercentiles(): void
    {
        $calculator = new StatisticsCalculator();
        $metrics = new BenchmarkMetrics(
            benchmarkId: '1',
            benchmarkName: 'Test',
            phpVersion: 'php84',
            executionTimes: [10.0, 20.0, 30.0, 40.0, 50.0],
            memoryUsages: [],
            memoryPeaks: [],
        );

        $statistics = $calculator->calculate($metrics);

        $this->assertEquals(30.0, $statistics->percentiles->p50);
        $this->assertEquals(50.0, $statistics->percentiles->p90);
    }
}
```

## Conclusion

The refactoring successfully applies:
- ✅ **Clean Architecture** (3 layers with correct dependencies)
- ✅ **SOLID Principles** (all 5 principles)
- ✅ **DDD** (Value Objects, Domain Services, Ports)
- ✅ **Hexagonal Architecture** (Ports & Adapters)

**Result:** Maintainable, testable, extensible code following industry best practices.
