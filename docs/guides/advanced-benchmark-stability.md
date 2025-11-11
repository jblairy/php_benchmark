# Advanced Benchmark Stability Techniques

## Current Status

Your benchmark system has already implemented:
- ✅ **Warmup iterations**: 10 iterations before measurement
- ✅ **Inner iterations**: 1000 iterations per measurement
- ✅ **High-resolution timer**: Using `hrtime()` for nanosecond precision
- ✅ **Docker isolation**: Each PHP version runs in its own container

These improvements have likely reduced CV% from ~30% to ~10-15%. Here are advanced techniques to achieve CV% < 5%.

## Advanced Optimization Strategies

### 1. CPU Affinity and Isolation

**Problem**: The OS scheduler moves processes between CPU cores, causing cache misses and performance variations.

**Solution**: Pin benchmark processes to specific CPU cores.

```php
// Add to ScriptBuilder before benchmark execution
if (function_exists('pcntl_setaffinity')) {
    // Pin to CPU core 0
    pcntl_setaffinity([0]);
}
```

**Docker Implementation**:
```yaml
# docker-compose.yml
services:
  php84:
    cpuset: "0,1"  # Restrict to cores 0 and 1
    cpu_shares: 1024  # Ensure consistent CPU allocation
```

**Impact**: 10-20% CV% reduction

### 2. Memory Pre-allocation and GC Control

**Problem**: PHP's garbage collector runs unpredictably, causing timing spikes.

**Solution**: Control GC timing and pre-allocate memory.

```php
// Enhanced ScriptBuilder
public function build(string $methodBody): string
{
    $warmupIterations = $this->warmupIterations;
    $innerIterations = $this->innerIterations;
    
    return <<<PHP
        // Disable GC during measurement
        \$gc_enabled = gc_enabled();
        gc_disable();
        
        // Pre-allocate memory to reduce allocation overhead
        \$dummy = str_repeat('x', 10 * 1024 * 1024); // 10MB
        unset(\$dummy);
        
        // Force GC before measurement
        gc_collect_cycles();
        
        // Warmup phase
        for (\$warmup = 0; \$warmup < {$warmupIterations}; ++\$warmup) {
            {$methodBody}
        }
        
        // Stabilization pause (let CPU caches settle)
        usleep(1000); // 1ms
        
        // Clear CPU cache (x86 specific)
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Measurement phase
        \$start_time = hrtime(true);
        
        for (\$inner = 0; \$inner < {$innerIterations}; ++\$inner) {
            {$methodBody}
        }
        
        \$end_time = hrtime(true);
        
        // Re-enable GC if it was enabled
        if (\$gc_enabled) {
            gc_enable();
        }
        
        // ... rest of measurement code
    PHP;
}
```

**Impact**: 5-10% CV% reduction

### 3. Statistical Outlier Detection

**Problem**: Occasional system interrupts create outliers that skew results.

**Solution**: Collect multiple samples and use robust statistics.

```php
// New BenchmarkResultWithStatistics class
final class BenchmarkResultWithStatistics
{
    /**
     * @param float[] $samples
     */
    public function __construct(
        private array $samples,
        public readonly float $median,
        public readonly float $mean,
        public readonly float $stdDev,
        public readonly float $cv,
        public readonly float $min,
        public readonly float $max,
        public readonly float $p95, // 95th percentile
        public readonly int $outlierCount,
    ) {}
    
    /**
     * @param float[] $samples
     */
    public static function fromSamples(array $samples): self
    {
        $filtered = self::removeOutliers($samples);
        sort($filtered);
        
        $count = count($filtered);
        $mean = array_sum($filtered) / $count;
        
        // Calculate standard deviation
        $variance = 0;
        foreach ($filtered as $sample) {
            $variance += pow($sample - $mean, 2);
        }
        $stdDev = sqrt($variance / $count);
        
        // Calculate percentiles
        $median = $filtered[(int)($count / 2)];
        $p95 = $filtered[(int)($count * 0.95)];
        
        return new self(
            samples: $filtered,
            median: $median,
            mean: $mean,
            stdDev: $stdDev,
            cv: ($stdDev / $mean) * 100,
            min: min($filtered),
            max: max($filtered),
            p95: $p95,
            outlierCount: count($samples) - count($filtered),
        );
    }
    
    /**
     * Remove outliers using Tukey's method
     * @param float[] $samples
     * @return float[]
     */
    private static function removeOutliers(array $samples): array
    {
        sort($samples);
        $count = count($samples);
        
        $q1 = $samples[(int)($count * 0.25)];
        $q3 = $samples[(int)($count * 0.75)];
        $iqr = $q3 - $q1;
        
        $lowerBound = $q1 - (1.5 * $iqr);
        $upperBound = $q3 + (1.5 * $iqr);
        
        return array_filter(
            $samples,
            fn($sample) => $sample >= $lowerBound && $sample <= $upperBound
        );
    }
}
```

### 4. Multi-Sample Execution Strategy

**Problem**: Single execution can be affected by transient system states.

**Solution**: Execute multiple independent runs and aggregate results.

```php
// Enhanced SingleBenchmarkExecutor
public function execute(Benchmark $benchmark, int $iterations): void
{
    $samples = [];
    $subIterations = max(3, (int)sqrt($iterations)); // At least 3 sub-runs
    
    for ($run = 0; $run < $subIterations; $run++) {
        // Execute with fresh process state
        $result = $this->executeWithIsolation($benchmark, $iterations / $subIterations);
        $samples[] = $result->executionTimeMs;
        
        // Inter-run stabilization
        usleep(10000); // 10ms pause
    }
    
    $stats = BenchmarkResultWithStatistics::fromSamples($samples);
    
    // Only accept if CV% is acceptable
    if ($stats->cv > 10) {
        $this->logger->warning('High CV% detected, increasing iterations', [
            'cv' => $stats->cv,
            'samples' => count($samples),
        ]);
        // Retry with more iterations
    }
}
```

### 5. Docker-Specific Optimizations

**Problem**: Docker adds overhead and variability.

**Solutions**:

#### A. Use Docker's `--privileged` mode for benchmarks
```yaml
# docker-compose.yml
services:
  php84:
    privileged: true  # Allows real-time scheduling
    ulimits:
      rtprio: 99  # Real-time priority
```

#### B. Pre-warm containers
```php
// DockerPoolExecutor enhancement
private function ensureContainerWarm(string $phpVersion): void
{
    if (!isset($this->warmContainers[$phpVersion])) {
        // Execute dummy script to warm up container
        $this->executeInDocker($phpVersion, 'echo "warm";');
        $this->warmContainers[$phpVersion] = true;
    }
}
```

#### C. Use tmpfs for script execution
```yaml
services:
  php84:
    tmpfs:
      - /tmp/benchmarks:size=100M,mode=1777
```

### 6. Advanced Timing Techniques

**Problem**: Even `hrtime()` can have variations.

**Solution**: Use TSC (Time Stamp Counter) directly:

```php
// Create a PHP extension or use FFI
final class TscTimer
{
    private \FFI $ffi;
    
    public function __construct()
    {
        $this->ffi = \FFI::cdef("
            unsigned long long rdtsc() {
                unsigned int lo, hi;
                __asm__ __volatile__ (\"rdtsc\" : \"=a\" (lo), \"=d\" (hi));
                return ((unsigned long long)hi << 32) | lo;
            }
        ");
    }
    
    public function getTicks(): int
    {
        return $this->ffi->rdtsc();
    }
}
```

### 7. Environment Preparation Script

Create a script to prepare the system for benchmarking:

```bash
#!/bin/bash
# prepare-benchmark-env.sh

# Disable CPU frequency scaling
sudo cpupower frequency-set -g performance

# Disable Intel Turbo Boost
echo 1 | sudo tee /sys/devices/system/cpu/intel_pstate/no_turbo

# Clear caches
sync && echo 3 | sudo tee /proc/sys/vm/drop_caches

# Stop unnecessary services
sudo systemctl stop snapd
sudo systemctl stop cups

# Set process priority
sudo nice -n -20 $@
```

### 8. Benchmark Configuration Profiles

Add different profiles for different stability requirements:

```yaml
# config/packages/benchmark.yaml
benchmark:
  profiles:
    fast:
      warmup_iterations: 5
      inner_iterations: 100
      statistical_runs: 3
      max_cv_percent: 15
      
    balanced:
      warmup_iterations: 10
      inner_iterations: 1000
      statistical_runs: 5
      max_cv_percent: 10
      
    precise:
      warmup_iterations: 20
      inner_iterations: 10000
      statistical_runs: 10
      max_cv_percent: 5
      
    scientific:
      warmup_iterations: 50
      inner_iterations: 100000
      statistical_runs: 20
      max_cv_percent: 2
```

## Implementation Priority

1. **Statistical Analysis** (Easy, High Impact)
   - Implement outlier removal
   - Use median instead of mean
   - Add CV% calculation

2. **Multi-Sample Execution** (Medium, High Impact)
   - Run multiple independent samples
   - Aggregate results statistically

3. **GC Control** (Easy, Medium Impact)
   - Disable GC during measurement
   - Force collection before measurement

4. **Docker Optimizations** (Medium, Medium Impact)
   - Use tmpfs for scripts
   - Pre-warm containers

5. **CPU Affinity** (Hard, High Impact)
   - Requires privileged mode
   - Platform-specific

## Expected Results

| Optimization | CV% Reduction | Implementation Effort |
|-------------|---------------|----------------------|
| Current (warmup + inner) | ~15% → ~10% | ✅ Done |
| Statistical Analysis | ~10% → ~7% | Low |
| Multi-Sample | ~7% → ~5% | Medium |
| GC Control | ~5% → ~4% | Low |
| Docker Optimizations | ~4% → ~3% | Medium |
| CPU Affinity | ~3% → ~2% | High |
| All Combined | ~15% → ~2% | - |

## Monitoring and Validation

Add metrics to track stability improvements:

```php
// New metrics to track
class BenchmarkMetrics
{
    public function __construct(
        public readonly float $cv,
        public readonly int $outlierCount,
        public readonly float $executionTimeMs,
        public readonly float $medianTimeMs,
        public readonly float $p95TimeMs,
        public readonly int $sampleCount,
        public readonly float $stabilityScore, // 0-100, higher is better
    ) {}
}
```

## Conclusion

With these advanced techniques, you can achieve CV% < 5% for most benchmarks, and < 2% for critical measurements. The key is to:

1. **Layer optimizations** - Each technique adds incremental improvement
2. **Measure the measurements** - Track CV% and stability metrics
3. **Adapt dynamically** - Use different profiles based on requirements
4. **Accept trade-offs** - Higher stability requires more execution time

Remember: The goal is not perfect stability (CV% = 0) but *sufficient* stability for meaningful comparisons.