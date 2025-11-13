# Utility Scripts

This directory contains utility scripts for working with the PHP Benchmark project.

## Analysis Scripts

### analyze-benchmark-iterations.php

**Purpose**: Analyzes benchmark iteration stability to determine optimal iteration counts

**Usage**:
```bash
php scripts/analyze-benchmark-iterations.php
```

**What it does**:
- Analyzes coefficient of variation (CV%) for each benchmark
- Suggests optimal iteration counts based on stability
- Generates recommendations for per-benchmark configuration

### update-benchmark-iterations.php

**Purpose**: Updates benchmark YAML files with calibrated iteration counts

**Usage**:
```bash
php scripts/update-benchmark-iterations.php
```

**What it does**:
- Reads calibration results
- Updates benchmark YAML files with optimal iterations
- Preserves existing configuration structure
