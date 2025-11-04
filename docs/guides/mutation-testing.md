# Mutation Testing with Infection

## Overview

Infection is a PHP mutation testing framework that tests the quality of your tests by introducing small changes (mutations) to your code and checking if your tests catch them.

## Why Mutation Testing?

- **Tests your tests**: Code coverage shows which lines are executed, but not if assertions are effective
- **Finds weak tests**: Reveals tests that pass even when code logic changes
- **Improves test quality**: Encourages writing better assertions
- **Higher confidence**: Ensures tests actually protect against regressions

## Installation

Infection is already installed as a dev dependency:

```bash
composer require --dev infection/infection
```

## Configuration

Configuration is in `infection.json5`:

```json5
{
  "$schema": "vendor/infection/infection/resources/schema.json",
  
  "source": {
    "directories": ["src/Domain", "src/Application"],
    "excludes": ["src/Domain/Benchmark/Test"]
  },
  
  "minMsi": 80,        // Minimum Mutation Score Indicator
  "minCoveredMsi": 85, // Minimum Covered Code MSI
  
  "mutators": {
    "@default": true
  }
}
```

## Running Infection

### Prerequisites

Infection requires a code coverage driver. Since the Docker container doesn't have Xdebug installed by default, there are two options:

#### Option 1: Using phpdbg (Recommended - Already Available)

```bash
# Generate coverage + run mutations
make infection-report
```

#### Option 2: Install PCOV Extension

Add to your `Dockerfile`:

```dockerfile
RUN pecl install pcov && docker-php-ext-enable pcov
```

Then rebuild:

```bash
docker-compose build main
make infection
```

### Commands

```bash
# Run mutation testing with thresholds (MSI >= 80%, Covered MSI >= 85%)
make infection

# Run mutation testing without threshold (for reports only)
make infection-report

# Generate test coverage first
make test-coverage
```

### Manual Execution

```bash
# With phpdbg
docker-compose run --rm main phpdbg -qrr vendor/bin/infection --threads=4

# With pre-generated coverage
docker-compose run --rm main phpdbg -qrr vendor/bin/phpunit --coverage-xml=var/coverage/coverage-xml --log-junit=var/coverage/junit.xml
docker-compose run --rm main vendor/bin/infection --coverage=var/coverage --threads=4

# Filter specific files
docker-compose run --rm main phpdbg -qrr vendor/bin/infection --filter=AsyncBenchmarkRunner.php

# Show mutations
docker-compose run --rm main phpdbg -qrr vendor/bin/infection --show-mutations
```

## Understanding Results

### Mutation Score Indicator (MSI)

```
MSI = (Killed mutations / Total mutations) × 100
```

- **Killed**: Test failed when mutation was applied ✅ (Good!)
- **Escaped**: Test passed despite mutation ❌ (Bad - weak test)
- **Uncovered**: Code not covered by tests ⚠️
- **Timeout**: Test took too long 🐌
- **Error**: Mutation caused fatal error 💥

### Score Interpretation

| MSI | Quality | Action |
|-----|---------|--------|
| 85%+ | Excellent | Maintain quality |
| 70-84% | Good | Improve weak tests |
| 50-69% | Fair | Add assertions |
| <50% | Poor | Review test strategy |

### Example Output

```
46 mutations were generated:
      23 mutants were killed
       3 mutants were not covered by tests
      20 mutants were escaped

Metrics:
    Mutation Score Indicator (MSI): 53%
    Mutation Code Coverage: 93%
    Covered Code MSI: 57%
```

## Improving Mutation Score

### 1. Escaped Mutations

**Problem**: Tests pass even when logic changes

```php
// Original code
public function isValid(): bool
{
    return $this->value > 0;  // Mutation: > becomes >=
}

// Weak test (passes with mutation)
public function testIsValid(): void
{
    $obj = new MyClass(5);
    self::assertTrue($obj->isValid());  // Still passes with >=
}

// Strong test (catches mutation)
public function testIsValidReturnsFalseForZero(): void
{
    $obj = new MyClass(0);
    self::assertFalse($obj->isValid());  // Fails with >=
}
```

### 2. Uncovered Code

Add tests for untested lines:

```bash
# Check coverage
docker-compose run --rm main vendor/bin/phpunit --coverage-text

# Target: > 80% line coverage
```

### 3. Common Mutations

| Mutator | Example | Fix |
|---------|---------|-----|
| `>` → `>=` | Boundary conditions | Test edge cases (0, -1) |
| `&&` → `\|\|` | Logic operators | Test both true/false |
| `+` → `-` | Math operators | Test calculations |
| `true` → `false` | Boolean values | Test boolean logic |
| `===` → `!==` | Comparisons | Test equality |

## Best Practices

### 1. Run Regularly

```bash
# In CI/CD (see .github/workflows/quality.yml)
- name: Run Infection
  run: make infection-report
```

### 2. Focus on Critical Code

```json5
{
  "source": {
    "directories": ["src/Domain", "src/Application"]  // Core logic only
  }
}
```

### 3. Set Realistic Thresholds

Start low, increase gradually:

```bash
# Initial run
--min-msi=60 --min-covered-msi=70

# After improvements
--min-msi=80 --min-covered-msi=85
```

### 4. Review Escaped Mutations

```bash
# Show specific mutations
make infection-report | grep "Escaped"

# Analyze HTML report
open var/infection/html/index.html
```

## Troubleshooting

### Error: No coverage driver available

**Solution 1**: Use phpdbg (already available)
```bash
make infection-report
```

**Solution 2**: Install PCOV
```dockerfile
RUN pecl install pcov && docker-php-ext-enable pcov
```

### Error: Tests failing randomly

Infection runs tests in random order. Fix test dependencies:

```xml
<!-- phpunit.xml -->
<phpunit
    executionOrder="default"
    resolveDependencies="true"
>
```

### Timeout errors

Increase timeout in `infection.json5`:

```json5
{
  "timeout": 20  // seconds
}
```

### Too slow

```bash
# Use more threads
--threads=8

# Skip initial tests (if coverage already generated)
--skip-initial-tests

# Filter specific files
--filter=src/Application/
```

## CI/CD Integration

Add to `.github/workflows/quality.yml`:

```yaml
- name: Run Mutation Testing
  run: |
    docker-compose run --rm main phpdbg -qrr vendor/bin/infection \
      --threads=4 \
      --min-msi=80 \
      --min-covered-msi=85 \
      --logger-github
```

## Resources

- [Infection Documentation](https://infection.github.io/)
- [Mutation Testing Introduction](https://infection.github.io/guide/mutation-testing.html)
- [Mutators Reference](https://infection.github.io/guide/mutators.html)
- [PHPUnit with Infection](https://infection.github.io/guide/phpunit.html)

## Example: AsyncBenchmarkRunner

Our `AsyncBenchmarkRunner` test suite has high mutation coverage:

```php
// Tests check:
- Event dispatching (BenchmarkStarted, Progress, Completed)
- Result persistence
- Async executor wait() call
- Multiple iterations handling

// Result: Strong mutation score because:
✅ Each assertion has a purpose
✅ Edge cases are tested (0, 1, multiple iterations)
✅ Mock expectations verify behavior
✅ No redundant tests
```

**Target**: Maintain MSI > 80% for Domain and Application layers.
