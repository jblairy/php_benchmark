# Iteration Calibration Guide

## Overview

Iteration calibration automatically determines optimal warmup and inner iteration values based on actual execution time measurements. This ensures benchmarks achieve target execution times (typically 1 second) while maintaining statistical accuracy.

## Why Calibrate?

### Problem
- Different benchmarks have vastly different execution times
- Static iteration values may cause timeouts or insufficient measurements
- PHP 5.6 (slowest) should be used as baseline for maximum compatibility

### Solution
- Measure actual execution time with minimal iterations
- Calculate optimal values to reach target time (1000ms default)
- Apply configuration to all PHP versions

## Calibration Command

### Basic Usage

```bash
# Calibrate a single benchmark
php bin/console benchmark:calibrate --benchmark=lower-with-mb-strtolower

# Calibrate all benchmarks
php bin/console benchmark:calibrate --all

# Dry run (preview without saving)
php bin/console benchmark:calibrate --all --dry-run
```

### Options

| Option | Short | Description | Default |
|--------|-------|-------------|---------|
| `--benchmark` | `-b` | Specific benchmark slug | - |
| `--all` | `-a` | Calibrate all benchmarks | - |
| `--target-time` | `-t` | Target execution time (ms) | 1000 |
| `--php-version` | `-p` | PHP version for calibration | php56 |
| `--dry-run` | - | Preview without saving | false |
| `--force` | `-f` | Recalibrate even if configured | false |

### Examples

**Calibrate with 500ms target (faster tests)**
```bash
php bin/console benchmark:calibrate --all --target-time=500
```

**Calibrate heavy operations only**
```bash
php bin/console benchmark:calibrate --benchmark=replace-with-preg-replace --target-time=1500
```

**Use PHP 8.4 as baseline (faster)**
```bash
php bin/console benchmark:calibrate --all --php-version=php84
```

## How It Works

### 1. Measure Baseline

```
Execute code once (code already contains loops)
Measure actual execution time
```

**Note:** Benchmark fixtures already contain their own loops (e.g., `for ($i = 0; $i < 100000; $i++)`). The calibration measures this single execution, then calculates how many times to repeat it.

### 2. Calculate Optimal Inner Iterations

```
optimal_inner = target_time / measured_time
```

**Example:**
- Measured time: 50ms for one execution
- Target: 1000ms
- Result: `inner = 1000 / 50 = 20`

### 3. Adjust Warmup Based on Execution Time

```
if measured_time > 100ms: warmup = 1  # Heavy benchmark
if measured_time > 50ms:  warmup = 3
if measured_time > 10ms:  warmup = 5
if measured_time > 1ms:   warmup = 10
else:                     warmup = 15  # Light benchmark
```

**Rationale:** Heavy operations need fewer warmup iterations, while light operations benefit from more warmup for CPU cache stability.

### 4. Clamp Values

```
inner: 10 to 1000
warmup: determined by complexity (see step 3)
```

## Calibration Strategy

### Best Practices

1. **Use PHP 5.6 as baseline**
   - Slowest version ensures all versions work
   - Prevents timeouts on older PHP

2. **Target 1 second execution time**
   - Good balance between speed and accuracy
   - Allows for 3-10 runs in reasonable time

3. **Re-calibrate when:**
   - Benchmark code changes significantly
   - Target hardware changes
   - After significant PHP version updates

4. **Don't calibrate:**
   - Loop performance tests (iteration IS the test)
   - Benchmarks testing specific iteration counts
   - When execution time is part of the test

### Workflow

```bash
# Step 1: Analyze current state
php scripts/analyze-benchmark-iterations.php

# Step 2: Calibrate all unconfigured benchmarks
php bin/console benchmark:calibrate --all --dry-run

# Step 3: Review and apply
php bin/console benchmark:calibrate --all

# Step 4: Reload fixtures
make db.refresh

# Step 5: Verify with actual runs
php bin/console benchmark:run --test=your-test --iterations=5
```

## Output Format

```
Benchmark Iteration Calibration
Target execution time: 1000 ms
Calibration PHP version: php56

 3/109 [▓░░░░░░░░░░░░░░░░░░░░░░░░░]

Calibration Results
┌─────────────────────────────┬───────────────┬────────┬───────┬────────────┐
│ Benchmark                   │ Measured Time │ Warmup │ Inner │ Efficiency │
├─────────────────────────────┼───────────────┼────────┼───────┼────────────┤
│ lower-with-mb-strtolower    │ 50.23 ms      │ 3      │ 20    │ 100%       │
│ replace-with-preg-replace   │ 45.78 ms      │ 3      │ 22    │ 98%        │
│ abs-with-abs                │ 2.15 ms       │ 10     │ 465   │ 100%       │
└─────────────────────────────┴───────────────┴────────┴───────┴────────────┘

✓ Calibrated 3 benchmarks
```

### Understanding Efficiency

- **100%**: Perfect match to target time
- **90-99%**: Very good (within 10% of target)
- **80-89%**: Good (within 20% of target)
- **<80%**: May need manual adjustment

## Manual Calibration Process

If automatic calibration isn't available:

### 1. Run Single Iteration

```bash
time php bin/console benchmark:run --test=your-benchmark --iterations=1 --php-version=php56
```

### 2. Calculate Time Per Iteration

```
actual_time = 5.2 seconds
current_inner = 100
time_per_inner = 5200ms / 100 = 52ms
```

### 3. Calculate Optimal Inner

```
target_time = 1000ms
optimal_inner = 1000 / 52 = 19.2 ≈ 20
```

### 4. Update Fixture

```yaml
warmupIterations: 3
innerIterations: 20
```

### 5. Verify

```bash
php bin/console benchmark:run --test=your-benchmark --iterations=1
```

Should complete in ~1 second.

## Troubleshooting

### Calibration Fails

**Symptom:** Command reports errors

**Solutions:**
```bash
# Check if Docker containers are running
docker compose ps

# Check if benchmark exists
php bin/console benchmark:run --test=your-benchmark --iterations=1

# Try with higher timeout
BENCHMARK_TIMEOUT=60 php bin/console benchmark:calibrate --benchmark=your-benchmark
```

### Efficiency Below 80%

**Symptom:** Projected time doesn't match target

**Causes:**
- Benchmark has high variability
- Measurement was affected by system load
- Code has conditional execution paths

**Solutions:**
```bash
# Re-run calibration
php bin/console benchmark:calibrate --benchmark=your-benchmark --force

# Use manual calibration
# Measure multiple times and average
```

### Timeouts After Calibration

**Symptom:** Benchmark still times out

**Solutions:**
```bash
# Reduce inner iterations manually
# Edit fixture: innerIterations: 10

# Or recalibrate with lower target
php bin/console benchmark:calibrate --benchmark=your-benchmark --target-time=500 --force
```

## Integration with CI/CD

### GitHub Actions

```yaml
- name: Calibrate Benchmarks
  run: |
    php bin/console benchmark:calibrate --all --dry-run > calibration.txt
    cat calibration.txt
    
    # Fail if too many benchmarks need calibration
    count=$(grep "UPDATE" calibration.txt | wc -l)
    if [ $count -gt 10 ]; then
      echo "Too many benchmarks need calibration: $count"
      exit 1
    fi
```

### Pre-commit Hook

```bash
#!/bin/bash
# .git/hooks/pre-commit

# Check for modified benchmarks
modified=$(git diff --cached --name-only | grep "fixtures/benchmarks/.*\.yaml")

if [ -n "$modified" ]; then
    echo "Benchmark fixtures modified, checking calibration..."
    php bin/console benchmark:calibrate --all --dry-run
    echo "Run 'php bin/console benchmark:calibrate --all' to apply changes"
fi
```

## Advanced: Adaptive Target Times

Different benchmark categories may benefit from different target times:

```bash
# Heavy operations: longer execution for accuracy
php bin/console benchmark:calibrate \
  --benchmark=hash-with-sha256 \
  --target-time=2000

# Light operations: shorter for speed
php bin/console benchmark:calibrate \
  --benchmark=abs-with-abs \
  --target-time=500

# Memory-intensive: longer for GC stability
php bin/console benchmark:calibrate \
  --benchmark=clone-with-serialize \
  --target-time=1500
```

## See Also

- [Per-Benchmark Iterations](per-benchmark-iterations.md) - Configuration system
- [Benchmark Iterations Guide](benchmark-iterations-guide.md) - Understanding layers
- [Improving Benchmark Stability](improving-benchmark-stability.md) - CV% optimization