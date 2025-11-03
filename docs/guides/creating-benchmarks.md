# Creating Benchmarks

⚠️ **Important**: As of November 2025, benchmarks are now defined as **YAML fixtures** instead of PHP classes.

📖 **See the complete guide**: [fixtures.md](fixtures.md)

## Quick Start (New Method - YAML Fixtures)

1. Create a YAML file in `fixtures/benchmarks/`
2. Define your benchmark metadata and code
3. Load with `make fixtures`

**Example:**
```yaml
# fixtures/benchmarks/string-concat.yaml
slug: string-concat
name: 'String Concatenation'
category: 'String Operations'
phpVersions: [php84, php85]
code: |
  $result = '';
  for ($i = 0; $i < 10000; $i++) {
      $result .= 'test' . $i;
  }
```

📖 **Full YAML fixtures guide**: [fixtures.md](fixtures.md)

---

## Legacy Method (PHP Classes - Deprecated)

This method is kept for reference but **new benchmarks should use YAML fixtures**.

### Quick Start

1. Create a class in `src/Domain/Benchmark/Test/`
2. Extend `AbstractBenchmark`
3. Add PHP version attributes
4. Implement your test method

## Example

```php
<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php80;

final class StringOperations extends AbstractBenchmark
{
    #[All]
    public function withConcatenation(): void
    {
        $result = '';
        for ($i = 0; $i < 10000; $i++) {
            $result .= 'test' . $i;
        }
    }

    #[Php80]
    public function withStringBuilder(): void
    {
        $parts = [];
        for ($i = 0; $i < 10000; $i++) {
            $parts[] = 'test' . $i;
        }
        $result = implode('', $parts);
    }
}
```

## PHP Version Attributes

### Available Attributes

| Attribute | Description | Use Case |
|-----------|-------------|----------|
| `#[All]` | Run on all PHP versions | Universal operations |
| `#[Php56]` | PHP 5.6 | Legacy code |
| `#[Php70]` | PHP 7.0 | |
| `#[Php71]` | PHP 7.1 | |
| `#[Php72]` | PHP 7.2 | |
| `#[Php73]` | PHP 7.3 | |
| `#[Php74]` | PHP 7.4 | Arrow functions |
| `#[Php80]` | PHP 8.0+ | Named arguments, match |
| `#[Php81]` | PHP 8.1+ | Enums, readonly properties |
| `#[Php82]` | PHP 8.2+ | Readonly classes |
| `#[Php83]` | PHP 8.3+ | Typed constants |
| `#[Php84]` | PHP 8.4+ | Property hooks |
| `#[Php85]` | PHP 8.5+ | Future features |

### Multiple Attributes

You can compare different implementations:

```php
final class ArrayOperations extends AbstractBenchmark
{
    #[All]
    public function withForeach(): void
    {
        $result = [];
        $data = range(1, 10000);
        foreach ($data as $item) {
            $result[] = $item * 2;
        }
    }

    #[All]
    public function withArrayMap(): void
    {
        $data = range(1, 10000);
        $result = array_map(fn($x) => $x * 2, $data);
    }
}
```

## Benchmark Organization

### Directory Structure

Group related benchmarks in subdirectories:

```
src/Domain/Benchmark/Test/
├── ArrayMap/
│   ├── MapWithArrayMap.php
│   └── MapWithForeach.php
├── StringConcatenation/
│   ├── ConcatenationWithDot.php
│   ├── ConcatenationWithImplode.php
│   └── ConcatenationWithInterpolation.php
└── Loop.php
```

### Naming Convention

- **Class name**: Describes what is being tested
- **Method name**: Describes the approach being used

```php
// Class: What is tested
final class StringConcatenation extends AbstractBenchmark
{
    // Method: How it's tested
    #[All]
    public function withDot(): void { }

    #[All]
    public function withInterpolation(): void { }
}
```

## Running Your Benchmark

```bash
# Run all benchmarks
make run

# Run specific benchmark
make run test=StringOperations

# Run with more iterations
make run test=StringOperations iterations=1000

# Run on specific PHP version
docker-compose run --rm main php bin/console benchmark:run \
    --test=StringOperations \
    --php-version=php84 \
    --iterations=100
```

## Best Practices

### 1. Keep Benchmarks Focused

✅ **Good** - Tests one thing:
```php
final class ArrayFilter extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $data = range(1, 10000);
        $result = array_filter($data, fn($x) => $x % 2 === 0);
    }
}
```

❌ **Bad** - Tests multiple things:
```php
final class ArrayOperations extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        $data = range(1, 10000);
        $result = array_filter($data, fn($x) => $x % 2 === 0);
        $result = array_map(fn($x) => $x * 2, $result);
        $result = array_reduce($result, fn($a, $b) => $a + $b, 0);
    }
}
```

### 2. Use Realistic Data Sizes

```php
// ✅ Good - Realistic size
$data = range(1, 10000); // ~10k items

// ❌ Too small - not representative
$data = range(1, 10);

// ❌ Too large - takes forever
$data = range(1, 10000000);
```

### 3. Avoid Side Effects

```php
// ✅ Good - Self-contained
#[All]
public function withLoop(): void
{
    $result = [];
    for ($i = 0; $i < 10000; $i++) {
        $result[] = $i;
    }
}

// ❌ Bad - Has side effects
#[All]
public function withFileWrite(): void
{
    for ($i = 0; $i < 10000; $i++) {
        file_put_contents('/tmp/benchmark.txt', $i, FILE_APPEND);
    }
}
```

### 4. Document Complex Benchmarks

```php
/**
 * Compares str_contains() (PHP 8.0+) vs strpos() for string search
 */
final class StringSearch extends AbstractBenchmark
{
    #[All]
    public function withStrpos(): void
    {
        $haystack = str_repeat('test', 1000);
        for ($i = 0; $i < 1000; $i++) {
            $found = strpos($haystack, 'needle') !== false;
        }
    }

    #[Php80]
    public function withStrContains(): void
    {
        $haystack = str_repeat('test', 1000);
        for ($i = 0; $i < 1000; $i++) {
            $found = str_contains($haystack, 'needle');
        }
    }
}
```

## View Results

After running benchmarks, view results in the dashboard:

```
http://localhost/dashboard
```

The dashboard shows:
- Execution time (ms)
- Memory usage (bytes)
- Percentiles (P50, P80, P90, P95, P99)
- Charts comparing PHP versions

## Examples from the Codebase

Browse existing benchmarks for inspiration:
- `src/Domain/Benchmark/Test/Loop.php` - Simple loop
- `src/Domain/Benchmark/Test/ArrayMap/` - Array operations comparison
- `src/Domain/Benchmark/Test/StringConcatenation/` - String operations
- `src/Domain/Benchmark/Test/MatchExpression/` - Match vs Switch (PHP 8.0+)
