# Using Outlier Detection for Better Benchmark Stability

## Overview

The enhanced statistics calculator automatically detects and removes outliers from benchmark data, resulting in more stable and reliable measurements.

## What are Outliers?

Outliers are data points that differ significantly from other observations. In benchmarking, they're often caused by:
- Garbage collection pauses
- System interrupts
- CPU throttling
- Cache misses
- Background process interference

## Implementation

### 1. Update Service Configuration

```yaml
# config/services.yaml
services:
    # Outlier Detector
    Jblairy\PhpBenchmark\Domain\Dashboard\Service\OutlierDetector: ~

    # Enhanced Statistics Calculator
    Jblairy\PhpBenchmark\Domain\Dashboard\Service\EnhancedStatisticsCalculator:
        arguments:
            $outlierDetector: '@Jblairy\PhpBenchmark\Domain\Dashboard\Service\OutlierDetector'
            $removeOutliers: true  # Enable outlier removal
            $outlierThreshold: 1.5  # IQR multiplier (1.5 = standard, 3.0 = extreme only)

    # Optional: Keep original calculator as fallback
    Jblairy\PhpBenchmark\Domain\Dashboard\Service\StatisticsCalculator:
        tags: ['statistics.calculator.legacy']
```

### 2. Use in Your Code

```php
use Jblairy\PhpBenchmark\Domain\Dashboard\Service\EnhancedStatisticsCalculator;
use Jblairy\PhpBenchmark\Domain\Dashboard\Model\BenchmarkMetrics;

// In your service
public function __construct(
    private readonly EnhancedStatisticsCalculator $statsCalculator,
) {}

public function analyzeBenchmark(BenchmarkMetrics $metrics): void
{
    $stats = $this->statsCalculator->calculate($metrics);
    
    // Access enhanced metrics
    $this->logger->info('Benchmark analysis', [
        'cv_before' => $stats->rawCV,
        'cv_after' => $stats->coefficientOfVariation,
        'outliers_removed' => $stats->outlierCount,
        'outlier_percentage' => $stats->outlierPercentage,
        'stability_score' => $stats->stabilityScore,
        'stability_rating' => $stats->getStabilityRating(),
    ]);
    
    // Check if benchmark is stable
    if (!$stats->isStable(10.0)) { // CV% > 10%
        $this->logger->warning(
            'Unstable benchmark detected',
            ['summary' => $stats->getOutlierSummary()]
        );
    }
}
```

## How It Works

### Tukey's Method (IQR)

1. **Calculate Quartiles**:
   - Q1 (25th percentile)
   - Q3 (75th percentile)
   - IQR = Q3 - Q1

2. **Define Bounds**:
   - Lower = Q1 - 1.5 × IQR
   - Upper = Q3 + 1.5 × IQR

3. **Remove Outliers**:
   - Any value < Lower or > Upper is removed

### Example

```
Data: [10, 11, 12, 13, 14, 15, 50, 11, 12, 13]
Q1 = 11, Q3 = 13.5, IQR = 2.5
Lower Bound = 11 - 1.5×2.5 = 7.25
Upper Bound = 13.5 + 1.5×2.5 = 17.25
Outlier: 50 (removed)
Clean Data: [10, 11, 12, 13, 14, 15, 11, 12, 13]
```

## Benefits

### Before Outlier Removal
```
Execution times: [12.5, 12.7, 45.2, 12.6, 98.7, 12.8, 12.9]
Average: 29.34 ms
Std Dev: 34.85
CV%: 118.8% (Very unstable!)
```

### After Outlier Removal
```
Execution times: [12.5, 12.7, 12.6, 12.8, 12.9]
Average: 12.7 ms
Std Dev: 0.14
CV%: 1.1% (Excellent stability!)
Outliers removed: 2 (28.6%)
```

## Configuration Options

### 1. Outlier Sensitivity

```php
// Conservative (only extreme outliers)
new EnhancedStatisticsCalculator($detector, true, 3.0);

// Standard (recommended)
new EnhancedStatisticsCalculator($detector, true, 1.5);

// Aggressive (more outliers removed)
new EnhancedStatisticsCalculator($detector, true, 1.0);
```

### 2. Alternative: Modified Z-Score

```php
// For smaller datasets or non-normal distributions
$result = $outlierDetector->detectWithModifiedZScore($data, 3.5);
```

## Monitoring

### Dashboard Enhancements

```twig
{# templates/components/BenchmarkCard.html.twig #}
<div class="benchmark-stats">
    <div class="cv-metric">
        <span class="label">CV%:</span>
        <span class="value {{ stats.isStable ? 'stable' : 'unstable' }}">
            {{ stats.coefficientOfVariation|number_format(1) }}%
        </span>
        {% if stats.outlierCount > 0 %}
            <span class="improvement">
                (↓{{ stats.getCVImprovement|number_format(0) }}%)
            </span>
        {% endif %}
    </div>
    
    {% if stats.outlierCount > 0 %}
    <div class="outlier-info">
        <small>{{ stats.getOutlierSummary }}</small>
    </div>
    {% endif %}
    
    <div class="stability-badge badge-{{ stats.getStabilityRating|lower }}">
        {{ stats.getStabilityRating }}
    </div>
</div>
```

### CLI Output

```bash
# Add to benchmark:run command
$io->info(sprintf(
    'Stability: %s (Score: %.1f/100) - %s',
    $stats->getStabilityRating(),
    $stats->stabilityScore,
    $stats->getOutlierSummary()
));
```

## Best Practices

1. **Always Log Outliers**: Keep track of how many outliers are being removed
2. **Investigate High Outlier Rates**: If >20% are outliers, investigate the cause
3. **Adjust Threshold Based on Use Case**:
   - Development: 1.5 (standard)
   - Production comparison: 1.5-2.0
   - Scientific/research: 3.0 (conservative)
4. **Combine with Other Techniques**:
   - Increase iterations for more data points
   - Use warmup to reduce initial outliers
   - Run during low system load

## Expected Improvements

| Metric | Before | After |
|--------|--------|--------|
| CV% | 15-30% | 5-10% |
| Outliers | N/A | 5-15% removed |
| Stability Score | 40-60 | 70-90 |
| False positives | High | Low |

## Next Steps

After implementing outlier detection:
1. Monitor the outlier percentage across different benchmarks
2. Adjust thresholds based on your specific needs
3. Consider implementing multi-sample execution (run benchmark multiple times)
4. Add CPU affinity and GC control for even better stability

## Troubleshooting

### Too Many Outliers Detected
- Increase threshold to 2.0 or 3.0
- Check for systematic issues (background processes, etc.)

### CV% Still High After Removal
- Increase warmup iterations
- Increase inner iterations
- Check for non-random variation sources

### No Improvement
- Dataset might be too small (need at least 10 samples)
- Variation might be systematic, not random