# Benchmark Iterations Configuration Guide

## Understanding Iteration Layers

The benchmark system uses multiple iteration layers to ensure stable and accurate measurements:

```
Total Operations = Fixture Iterations × Inner Iterations × Benchmark Runs
```

### 1. **Fixture Iterations** (in YAML files)
- Defined in each benchmark fixture
- Makes the operation measurable
- **NOT** related to statistical accuracy (except for loop benchmarks)

### 2. **Inner Iterations** (BENCHMARK_INNER_ITERATIONS)
- Controlled by environment variable
- Reduces measurement noise
- Default: 100

### 3. **Warmup Iterations** (BENCHMARK_WARMUP_ITERATIONS)
- Stabilizes JIT/OpCache
- Not included in measurements
- Default: 10

### 4. **Benchmark Runs** (--iterations flag)
- Number of complete benchmark executions
- For statistical analysis
- Default: 3

## Configuration Examples

### Quick Development Testing
```bash
# .env.local
BENCHMARK_WARMUP_ITERATIONS=3
BENCHMARK_INNER_ITERATIONS=10

# Result: Fast but less accurate
# 100k fixture × 10 inner × 3 runs = 3M operations
```

### Balanced (Default)
```bash
# .env
BENCHMARK_WARMUP_ITERATIONS=10
BENCHMARK_INNER_ITERATIONS=100

# Result: Good balance
# 100k fixture × 100 inner × 3 runs = 30M operations
```

### High Precision
```bash
# .env.local
BENCHMARK_WARMUP_ITERATIONS=20
BENCHMARK_INNER_ITERATIONS=500

# Result: Very stable measurements
# 100k fixture × 500 inner × 10 runs = 500M operations
```

## Why So Many Iterations?

### Fixture Iterations
Most benchmarks use high iteration counts (10k-100k) in fixtures because:
- Single operations are too fast to measure accurately
- Need to overcome timer resolution limits
- Amortize function call overhead

**Exception**: Loop performance benchmarks where the iteration IS the test.

### Inner Iterations
Added for stability:
- Reduces impact of system interrupts
- Averages out CPU frequency changes
- Minimizes GC impact

### Warmup Iterations
Critical for modern PHP:
- JIT compilation (PHP 8+)
- OpCache optimization
- CPU cache warming

## Troubleshooting

### Benchmark Timeouts
If benchmarks timeout with default settings:

1. **Check fixture iterations**:
   ```bash
   grep "for.*$i" fixtures/benchmarks/your-benchmark.yaml
   ```

2. **Temporarily reduce inner iterations**:
   ```bash
   BENCHMARK_INNER_ITERATIONS=10 make run test=your-benchmark
   ```

3. **Consider if the operation is inherently slow**:
   - `mb_*` functions: Reduce iterations
   - `preg_*` functions: Complex patterns need fewer iterations
   - File/Network I/O: Should not be in tight loops

### High CV% (Coefficient of Variation)
If CV% > 10%:

1. **Increase inner iterations**:
   ```bash
   BENCHMARK_INNER_ITERATIONS=500 make run test=your-benchmark
   ```

2. **Increase warmup**:
   ```bash
   BENCHMARK_WARMUP_ITERATIONS=20 make run test=your-benchmark
   ```

3. **Run more iterations**:
   ```bash
   make run test=your-benchmark iterations=10
   ```

## Best Practices

1. **Don't modify fixture iterations** unless:
   - The benchmark tests loop performance
   - The operation is extremely slow
   - You're creating a new benchmark

2. **Adjust environment variables** for different scenarios:
   - Development: Low iterations for speed
   - CI/CD: Balanced settings
   - Performance regression: High precision

3. **Monitor execution time**:
   - If a single run takes > 5 seconds, reduce iterations
   - If CV% > 10%, increase iterations

4. **Category-specific recommendations**:
   - **String operations**: 100k fixture iterations
   - **Array operations**: 10k-50k fixture iterations  
   - **Heavy operations** (regex, mb_*): 10k fixture iterations
   - **Loop benchmarks**: As defined by the test

## Example: Adjusting for Heavy Operations

For a benchmark using `mb_strtolower`:

```yaml
# Original (might timeout)
for ($i = 0; 100000 > $i; ++$i) {
    $result = mb_strtolower($text);
}
```

Options:
1. Reduce environment iterations:
   ```bash
   BENCHMARK_INNER_ITERATIONS=50 make run test=lower-with-mb-strtolower
   ```

2. Or modify fixture (if creating new benchmark):
   ```yaml
   # Reduced for heavy operation
   for ($i = 0; 10000 > $i; ++$i) {
       $result = mb_strtolower($text);
   }
   ```

## Summary

- **Fixture iterations**: Part of the benchmark definition
- **Inner iterations**: Adjust for stability (ENV variable)
- **Warmup iterations**: Adjust for consistency (ENV variable)
- **Benchmark runs**: Adjust for statistical confidence (CLI flag)

The default settings (warmup=10, inner=100) work well for most benchmarks. Adjust only when needed for specific scenarios.