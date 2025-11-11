# Improving Benchmark Stability (Reducing CV%)

## Problem: High Coefficient of Variation (CV%)

A CV% of ~30% indicates high variability in benchmark results, which reduces the reliability of performance measurements.

**What is CV%?**
```
CV% = (Standard Deviation / Mean) × 100
```

A CV% of 30% means results vary by ±30% from the mean, making it hard to detect real performance differences.

## Root Causes

### 1. **Insufficient Iterations per Test**
- Each benchmark runs only once per iteration
- Single runs are affected by system noise
- Need multiple inner loops to average out noise

**Current:** Single execution per iteration
**Target:** Thousands of executions per iteration

### 2. **Docker Container Overhead**
- Each PHP version runs in a separate container
- Cold starts and initialization overhead
- No JIT compilation warmup (PHP 8.0+)

### 3. **System Noise**
- Background processes
- CPU frequency scaling
- Disk I/O
- Memory pressure

### 4. **No Warmup Phase**
- First execution includes JIT compilation
- Opcode cache initialization
- PHP runtime setup

### 5. **Measurement Overhead**
- `microtime()` function calls
- `memory_get_usage()` calls
- JSON encoding/output

## Solutions

### 1. **Add Inner Loop to Benchmark Code**

Instead of:
```php
for ($i = -50000; 50000 > $i; ++$i) {
    $result = abs($i);
}
```

Use:
```php
// Run the test 1000 times to reduce per-iteration noise
for ($outer = 0; $outer < 1000; ++$outer) {
    for ($i = -50000; 50000 > $i; ++$i) {
        $result = abs($i);
    }
}
```

**Impact:** Reduces CV% by 50-70% by amortizing system noise.

### 2. **Implement Warmup Phase**

Modify `InstrumentedScriptBuilder` to run code before measurement:

```php
// Warmup: Run code 10 times without measurement
for ($w = 0; $w < 10; ++$w) {
    {$methodBody}
}

// Measurement: Run code and collect metrics
$start_time = microtime(true);
// ... rest of measurement code
```

**Impact:** Stabilizes JIT compilation and opcode cache for PHP 8.0+.

### 3. **Increase Iterations**

Instead of 3 iterations, use 10-20 iterations per test.

- **Trade-off:** Slower but more accurate
- **CLI:** `make run test=abs-with-abs iterations=20`

### 4. **Use High-Resolution Timer**

Replace `microtime()` with `hrtime()` (available since PHP 7.3):

```php
$start = hrtime(true);
// ... code ...
$end = hrtime(true);
$execution_time_ms = ($end - $start) / 1_000_000;
```

**Benefits:** Better precision, less affected by system jitter.

### 5. **Fix CPU Frequency**

If running on Linux, disable CPU frequency scaling:

```bash
# Check current governor
cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_governor

# Set to performance (requires root)
sudo bash -c 'for cpu in /sys/devices/system/cpu/cpu*/cpufreq/scaling_governor; do echo performance > "$cpu"; done'
```

### 6. **Isolate Test Environment**

- Close unnecessary applications
- Disable background services
- Run on dedicated hardware
- Minimize I/O operations

### 7. **Statistical Analysis**

- Use median instead of mean (less affected by outliers)
- Calculate confidence intervals
- Detect and remove outliers (>3 standard deviations)

## Recommended Implementation

### Priority 1: Add Inner Loops to Benchmarks
**Effort:** Easy | **Impact:** Very High (50-70% CV% reduction)

Update benchmark fixtures to include inner loops:
```yaml
# Instead of 100,000 iterations total
# Do 1,000 iterations × 1,000 inner loops = 100M operations
```

### Priority 2: Implement Warmup Phase
**Effort:** Medium | **Impact:** High (20-40% CV% reduction)

Modify `InstrumentedScriptBuilder.php` to add warmup loop.

### Priority 3: Increase Iterations to 10+
**Effort:** Easy | **Impact:** Medium (15-25% CV% reduction)

Default iterations in CLI and UI.

### Priority 4: Use hrtime() for Measurement
**Effort:** Low | **Impact:** Medium (5-10% CV% reduction)

Update timing measurement code.

## Expected Results

With all improvements implemented:

| Current | After P1 | After P1+P2 | After All |
|---------|----------|------------|-----------|
| CV% ~30% | ~10-15% | ~8-10% | ~5-8% |
| Interpretation | Very noisy | Reliable | Highly reliable |

## Verification

After making changes, compare results:

```bash
# Run same benchmark multiple times
for i in {1..5}; do
    make run test=abs-with-abs iterations=20
done

# Calculate CV% from results
# CV% should decrease with each implementation
```

## References

- [PHP Benchmarking Best Practices](https://www.php.net/manual/en/function.microtime.php)
- [Statistical Analysis in Benchmarking](https://easyperf.net/blog/2019/08/02/Perf-analysis-frame-of-mind-P1)
- [JIT Compilation in PHP 8](https://www.php.net/manual/en/book.jit.php)
