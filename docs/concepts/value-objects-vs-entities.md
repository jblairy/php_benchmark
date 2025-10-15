# Value Objects vs Entities

## Quick Reference

| Aspect | Value Object | Entity |
|--------|-------------|--------|
| **Identity** | No | Yes (has ID) |
| **Mutability** | Immutable | Mutable |
| **Equality** | By value | By ID |
| **Keyword** | `readonly` | mutable |
| **Lifecycle** | Created and discarded | Created, modified, deleted |
| **Example** | Money, Email, Address | User, Order, Product |

## Value Object

### Definition
A Value Object is defined **by its values**, not by identity. Two Value Objects with the same values are considered identical.

### Characteristics
- ✅ **Immutable** - once created, cannot be modified
- ✅ **No identity** - no ID field
- ✅ **Equality by value** - compared by their attributes
- ✅ **Replaceable** - if you need to change it, create a new one
- ✅ **Side-effect free** - methods don't modify state

### Example
```php
// Value Object - defined by its values
final readonly class BenchmarkConfiguration
{
    public function __construct(
        public Benchmark $benchmark,
        public PhpVersion $phpVersion,
        public int $iterations,
    ) {
        if ($iterations <= 0) {
            throw new InvalidArgumentException('Iterations must be positive');
        }
    }
}

// Two configs with same values are identical
$config1 = new BenchmarkConfiguration($bench, PhpVersion::PHP_8_4, 100);
$config2 = new BenchmarkConfiguration($bench, PhpVersion::PHP_8_4, 100);
// $config1 == $config2 → true (same values)
```

### Real-world Analogy
**Money**: €50 is €50, regardless of which physical bill you have. The value matters, not the identity of the bill.

### When to Use
- ✅ Measures (Distance, Weight, Temperature)
- ✅ Quantities (Money, Percentage)
- ✅ Business identifiers (Email, PhoneNumber, ISBN)
- ✅ Coordinates (Address, GeoLocation)
- ✅ Encapsulate validation logic

## Entity

### Definition
An Entity is defined **by its identity**, not its attributes. Two Entities with the same ID are the same object, even if attributes differ.

### Characteristics
- ✅ **Has identity** - typically an ID field
- ✅ **Mutable** - can change state over time
- ✅ **Equality by ID** - compared by identifier
- ✅ **Lifecycle** - created, modified, deleted
- ✅ **Continuity** - same entity through time despite changes

### Example
```php
// Entity - defined by identity
#[ORM\Entity]
class Pulse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private int $id;

    private float $executionTimeMs;  // can change
    private string $name;             // can change

    public function updateExecutionTime(float $time): void
    {
        $this->executionTimeMs = $time; // Mutable - OK for entities
    }
}

// Two entities with same ID are the same, even with different values
$pulse1 = Pulse::find(42); // execution time: 10ms
$pulse2 = Pulse::find(42); // execution time: 15ms (after update)
// $pulse1->id === $pulse2->id → true (same entity, different state)
```

### Real-world Analogy
**Person**: You are the same person even if you change your name, address, or appearance. Your identity (SSN, DNA) defines you, not your attributes.

### When to Use
- ✅ Objects with unique identity (User, Order, Product)
- ✅ Objects that change over time
- ✅ Objects persisted in database
- ✅ Objects with lifecycle

## In Our Benchmark Application

### Value Objects
```
Domain/Benchmark/Model/
├── BenchmarkConfiguration.php   # Config for one execution
├── BenchmarkResult.php          # Results of execution
└── ExecutionContext.php         # Execution environment data
```

**Why Value Objects?**
- No identity needed (no ID)
- Immutable (readonly)
- Compared by values
- Created and discarded frequently

**Example:**
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

### Entities
```
Infrastructure/Persistence/Doctrine/Entity/
└── Pulse.php                    # Persisted benchmark result (has ID)
```

**Why Entity?**
- Has an ID from database
- Mutable (can be updated)
- Has a lifecycle (persisted, retrieved, deleted)

**Example:**
```php
#[ORM\Entity]
class Pulse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private int $id;

    private float $executionTimeMs;
}
```

## Best Practices

### Value Object Rules
```php
// ✅ Good: Immutable, readonly
final readonly class BenchmarkResult
{
    public function __construct(
        public float $executionTimeMs,
        public float $memoryUsedBytes,
    ) {}
}

// ❌ Bad: Mutable setter
class BenchmarkResult
{
    private float $executionTimeMs;

    public function setExecutionTime(float $time): void
    {
        $this->executionTimeMs = $time; // Breaking immutability!
    }
}
```

### Entity Rules
```php
// ✅ Good: Has ID, mutable state
#[ORM\Entity]
class Pulse
{
    #[ORM\Id]
    private int $id;

    private float $executionTimeMs;

    public function updateExecutionTime(float $time): void
    {
        $this->executionTimeMs = $time; // OK for entities
    }
}
```

## Summary

| Our Classes | Type | Why |
|------------|------|-----|
| `BenchmarkConfiguration` | **Value Object** | No ID, immutable, defined by values |
| `BenchmarkResult` | **Value Object** | No ID, immutable, defined by values |
| `ExecutionContext` | **Value Object** | No ID, immutable, defined by values |
| `Pulse` | **Entity** | Has ID from DB, mutable, persisted |
| `Benchmark` (abstract) | **Specification** | Stateless, identified by class |
