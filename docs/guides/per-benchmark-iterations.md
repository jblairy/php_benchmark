# Per-Benchmark Iteration Configuration

## Overview

The system now supports configuring warmup and inner iterations on a per-benchmark basis. This allows for optimal execution time while maintaining scientific accuracy (CV% < 5%).

## Why Per-Benchmark Configuration?

Different benchmarks have different computational costs:

- **Heavy operations** (`mb_*`, `preg_*`, `hash()`): Need fewer iterations to avoid timeouts
- **Light operations** (`$i + 1`, property access): Need more iterations for measurable results
- **Loop benchmarks** (`foreach`, `for`): The iteration IS the test, so minimal inner loops

**Problem without configuration:**
```
mb_strtolower with 100k loops × 1000 inner = 100M operations = TIMEOUT!
```

**Solution with configuration:**
```
mb_strtolower with 100k loops × 20 inner = 2M operations = ~1 second ✓
```

## Database Schema

Two new nullable columns in `benchmarks` table:

```sql
ALTER TABLE benchmarks ADD warmup_iterations INT DEFAULT NULL;
ALTER TABLE benchmarks ADD inner_iterations INT DEFAULT NULL;
```

- `NULL` = use smart defaults based on code analysis
- Integer value = explicit configuration

## YAML Fixtures

Add iteration configuration to any benchmark fixture:

```yaml
slug: lower-with-mb-strtolower
name: 'Lower With Mb Strtolower'
category: AdvancedStrings
warmupIterations: 3   # NEW: Custom warmup
innerIterations: 20    # NEW: Custom inner iterations
code: |
  $text = 'HELLO WORLD';
  for ($i = 0; 100000 > $i; ++$i) {
      $result = mb_strtolower($text);
  }
```

## Smart Defaults

If no configuration is specified, the system analyzes the code:

### Complexity Analysis

1. **Count loop iterations** in the code
2. **Detect heavy operations** (`mb_*`, `preg_*`, `serialize()`, etc.)
3. **Calculate complexity score** = log10(iterations) × operation_weight
4. **Determine optimal values**

### Default Values by Complexity

| Complexity Level | Score | Warmup | Inner | Example |
|-----------------|-------|--------|-------|---------|
| Extreme | ≥15 | 3 | 20 | `mb_*` with 100k loops |
| Heavy | 10-15 | 5 | 50 | `preg_*` with 50k loops |
| Moderate | 5-10 | 10 | 100 | String operations with 10k loops |
| Light | 2-5 | 15 | 200 | Math operations |
| Minimal | <2 | 20 | 500 | Simple assignments |

### Special Categories

- **Iteration/Loop benchmarks**: warmup=5, inner=10 (the loop IS the test)

## Architecture

### Domain Layer

**`IterationConfiguration`** (Value Object)
```php
$config = IterationConfiguration::createWithDefaults(
    warmupIterations: null,  // Will analyze code
    innerIterations: null,   // Will suggest optimal
    benchmarkCode: $code,    // For analysis
);

echo $config->warmupIterations;  // 3
echo $config->innerIterations;   // 20
```

### Infrastructure Layer

**`ConfigurableScriptBuilder`** (Port Implementation)
```php
$builder = new ConfigurableScriptBuilder();
$builder->setIterationConfiguration($config);
$script = $builder->build($benchmarkCode);
```

**`ConfigurableSingleBenchmarkExecutor`**
- Fetches benchmark entity from repository
- Extracts custom iterations (if any)
- Creates IterationConfiguration
- Passes to ScriptBuilder

## Tools & Scripts

### 1. Analyze Benchmarks

```bash
php scripts/analyze-benchmark-iterations.php
```

Output:
```
=== COMPLEXITY DISTRIBUTION ===
Extreme (>15)       :  13 benchmarks
Heavy (10-15)       :   3 benchmarks
Moderate (5-10)     :  34 benchmarks

=== HEAVY BENCHMARKS (need reduced iterations) ===
replace-with-preg-replace     : complexity=20.0, ops=preg_, suggest: warmup=3, inner=20
lower-with-mb-strtolower      : complexity=15.0, ops=mb_, suggest: warmup=3, inner=20
```

### 2. Update Fixtures Automatically

```bash
# Dry run (preview changes)
php scripts/update-benchmark-iterations.php --dry-run

# Apply changes
php scripts/update-benchmark-iterations.php
```

Output:
```
UPDATE lower-with-mb-strtolower             warmup= 3 inner=  20 (heavy:mb_)
UPDATE replace-with-preg-replace            warmup= 3 inner=  20 (heavy:preg_)
SKIP   abs-with-abs                         (default values OK)
```

## Configuration Examples

### Heavy Regex Operation
```yaml
slug: replace-with-preg-replace
warmupIterations: 3
innerIterations: 20
code: |
  for ($i = 0; 100000 > $i; ++$i) {
      $result = preg_replace('/test/', 'sample', $text);
  }
```

Result: 100k × 20 = 2M operations (~2 seconds)

### Moderate String Operation
```yaml
slug: concatenation-with-dot
warmupIterations: 10
innerIterations: 100
code: |
  for ($i = 0; 10000 > $i; ++$i) {
      $result = 'Hello' . ' ' . 'World';
  }
```

Result: 10k × 100 = 1M operations (~0.5 seconds)

### Loop Performance Test
```yaml
slug: iterate-with-foreach
category: Iteration
# No custom iterations needed - smart defaults detect category
code: |
  $data = range(1, 10000);
  foreach ($data as $value) {
      $sum += $value;
  }
```

Result: Auto-detected as loop test, uses warmup=5, inner=10

## Migration Guide

### Existing Projects

1. **Run migration**:
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

2. **Analyze current benchmarks**:
   ```bash
   php scripts/analyze-benchmark-iterations.php
   ```

3. **Update fixtures** (or let smart defaults handle it):
   ```bash
   php scripts/update-benchmark-iterations.php
   ```

4. **Reload fixtures**:
   ```bash
   make db.refresh
   ```

### New Benchmarks

When creating a new benchmark, consider:

1. **Heavy operations?** Add explicit iterations
2. **Loop test?** Use category `Iteration` or `Loop`
3. **Standard operation?** Omit fields, let smart defaults decide

## Best Practices

### DO ✅

- Use explicit configuration for known heavy operations
- Use category-based detection for loop tests
- Trust smart defaults for most benchmarks
- Run analysis script after bulk changes

### DON'T ❌

- Don't set inner iterations too low (<10) - affects statistical validity
- Don't set too high for heavy operations - causes timeouts
- Don't forget to reload fixtures after updates
- Don't hardcode values without measuring first

## Performance Impact

| Configuration | Before | After | Improvement |
|--------------|--------|-------|-------------|
| `mb_strtolower` | Timeout (>30s) | ~1s | 30x faster |
| `preg_replace` | Timeout (>30s) | ~2s | 15x faster |
| All benchmarks | Variable | Consistent | Predictable |

## Troubleshooting

### Benchmark Still Times Out

1. Check fixture iterations:
   ```bash
   grep -A5 "slug: your-benchmark" fixtures/benchmarks/*.yaml
   ```

2. Reduce inner iterations:
   ```yaml
   innerIterations: 10  # Minimum for validity
   ```

3. Consider reducing fixture loop count

### CV% Still High (>10%)

1. Increase inner iterations:
   ```yaml
   innerIterations: 200
   ```

2. Increase warmup:
   ```yaml
   warmupIterations: 20
   ```

3. Run more benchmark iterations:
   ```bash
   php bin/console benchmark:run --iterations=10
   ```

## API Reference

### IterationConfiguration

```php
// Create with explicit values
$config = new IterationConfiguration(
    warmupIterations: 10,
    innerIterations: 100,
);

// Create with smart defaults
$config = IterationConfiguration::createWithDefaults(
    warmupIterations: null,  // Auto-calculate
    innerIterations: null,   // Auto-calculate
    benchmarkCode: $code,    // For analysis
);

// Get total operations
$total = $config->getTotalMeasurementIterations(); // Returns innerIterations

// Get description
echo $config->getDescription(); 
// Output: "Warmup: 10, Inner: 100 (Total: 100 measurements)"
```

### Benchmark Entity

```php
$benchmark->getWarmupIterations(); // ?int
$benchmark->getInnerIterations();  // ?int
$benchmark->setWarmupIterations(5);
$benchmark->setInnerIterations(50);
```

## See Also

- [Benchmark Iterations Guide](benchmark-iterations-guide.md) - Understanding iteration layers
- [Improving Benchmark Stability](improving-benchmark-stability.md) - CV% reduction techniques
- [Advanced Benchmark Stability](advanced-benchmark-stability.md) - Expert optimizations