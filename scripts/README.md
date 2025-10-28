# Utility Scripts

This directory contains utility scripts for working with the PHP Benchmark project.

## Mercure Scripts

### mercure-verify.sh

**Purpose**: Verifies that Mercure is properly configured and working

**Usage**:
```bash
./scripts/mercure-verify.sh
```

**What it checks**:
- ✅ Mercure container is running
- ✅ Mercure is accessible via HTTP
- ✅ Port 3000 is properly mapped
- ✅ Environment variables are set correctly
- ✅ Mercure bundle configuration exists
- ✅ Event subscriber is registered
- ✅ No errors in recent logs
- ✅ CORS is configured

**Output example**:
```
╔════════════════════════════════════════════════════════════════╗
║            Mercure Configuration Verification                  ║
╚════════════════════════════════════════════════════════════════╝

1. Checking Mercure container status...
   ✅ Mercure container is running

2. Checking Mercure HTTP accessibility...
   ✅ Mercure is accessible (HTTP 400 expected)

...

Summary: 15 passed, 0 failed
════════════════════════════════════════════════════════════════
🎉 All checks passed! Mercure is working correctly.
```

**Exit codes**:
- `0` - All checks passed
- `1` - One or more checks failed

---

### mercure-listen.sh

**Purpose**: Listen to Mercure events with formatted output

**Usage**:
```bash
./scripts/mercure-listen.sh [topic] [format]
```

**Parameters**:
- `topic` (optional): Mercure topic to subscribe to (default: `benchmark/progress`)
- `format` (optional): Output format - `raw`, `json`, `pretty`, `stats` (default: `pretty`)

**Formats**:

**1. pretty** (default) - Formatted output with colors and timestamps
```bash
./scripts/mercure-listen.sh
# or
./scripts/mercure-listen.sh benchmark/progress pretty
```

Output:
```
📨 Event ID: bc8bc7a8-e0c5-40af-8547-dd21fc4a7306
⏱️  benchmark.started at 12:34:56
   Benchmark: Loop on php84 (5 iterations)

📨 Event ID: 11031f73-897a-4d97-bf13-debe15b5ef20
🔄 benchmark.progress at 12:34:57
   Loop: 1/5 (20%)
```

**2. raw** - Raw Server-Sent Events format
```bash
./scripts/mercure-listen.sh benchmark/progress raw
```

Output:
```
id: urn:uuid:bc8bc7a8-e0c5-40af-8547-dd21fc4a7306
data: {"type":"benchmark.started","benchmarkId":"Loop",...}

id: urn:uuid:11031f73-897a-4d97-bf13-debe15b5ef20
data: {"type":"benchmark.progress","currentIteration":1,...}
```

**3. json** - JSON only (requires `jq`)
```bash
./scripts/mercure-listen.sh benchmark/progress json
```

Output:
```json
{
  "type": "benchmark.started",
  "benchmarkId": "Jblairy\\PhpBenchmark\\Domain\\Benchmark\\Test\\Loop",
  "benchmarkName": "Loop",
  "phpVersion": "php84",
  "totalIterations": 5,
  "timestamp": 1761113607
}
```

**4. stats** - Event statistics
```bash
./scripts/mercure-listen.sh benchmark/progress stats
```

Output (updates in real-time):
```
Event Statistics (Ctrl+C to stop and show results)

Started: 1 | Progress: 5 | Completed: 1
```

**Requirements**:
- `jq` (optional, for `json` and `pretty` formats)
  ```bash
  apt-get install jq  # Debian/Ubuntu
  ```

---

### mercure-test.sh

**Purpose**: End-to-end test of Mercure real-time events

**Usage**:
```bash
./scripts/mercure-test.sh [iterations] [test] [version]
```

**Parameters**:
- `iterations` (optional): Number of iterations (default: 3)
- `test` (optional): Benchmark to run (default: Loop)
- `version` (optional): PHP version (default: php84)

**Examples**:

**Quick test** (3 iterations):
```bash
./scripts/mercure-test.sh
```

**Custom test**:
```bash
./scripts/mercure-test.sh 10 Loop php84
```

**What it does**:
1. Starts listening to Mercure in background
2. Runs the specified benchmark
3. Captures all SSE events
4. Validates event counts
5. Shows results and sample event

**Output example**:
```
╔════════════════════════════════════════════════════════════════╗
║              Mercure End-to-End Test                           ║
╚════════════════════════════════════════════════════════════════╝

Configuration:
  Test: Loop
  PHP Version: php84
  Iterations: 3

Starting event listener in background...
Running benchmark...

Running Loop on php84 (3 iterations)
====================================
 [OK] Benchmark(s) completed successfully!

════════════════════════════════════════════════════════════════
Results:
════════════════════════════════════════════════════════════════

Events received:
  📨 Total events: 5
  🚀 Started: 1
  🔄 Progress: 3
  ✅ Completed: 1

Expected:
  📨 Total events: 5
  🚀 Started: 1
  🔄 Progress: 3
  ✅ Completed: 1

════════════════════════════════════════════════════════════════
🎉 All tests passed! Mercure real-time events are working correctly.

Sample event:
{
    "type": "benchmark.started",
    "benchmarkId": "Jblairy\\PhpBenchmark\\Domain\\Benchmark\\Test\\Loop",
    "benchmarkName": "Loop",
    "phpVersion": "php84",
    "totalIterations": 3,
    "timestamp": 1761113981
}
```

**Exit codes**:
- `0` - All tests passed
- `1` - Test failed

---

## Common Workflows

### 1. Initial Setup Verification

After setting up the project, verify Mercure is working:

```bash
# 1. Verify configuration
./scripts/mercure-verify.sh

# 2. Run end-to-end test
./scripts/mercure-test.sh

# 3. If all passes, you're ready!
```

---

### 2. Debug Real-Time Issues

If events are not showing in the browser:

```bash
# Terminal 1: Listen to events
./scripts/mercure-listen.sh

# Terminal 2: Run a benchmark
make run test=Loop iterations=5 version=php84

# Check if Terminal 1 receives events
# If yes: Problem is in browser/JavaScript
# If no: Problem is in Symfony publishing
```

---

### 3. Monitor Benchmark Execution

Watch benchmark progress in real-time:

```bash
# Start listener with pretty formatting
./scripts/mercure-listen.sh benchmark/progress pretty

# In another terminal, run benchmarks
make run iterations=10
```

---

### 4. Automated Testing (CI/CD)

Use in CI/CD pipelines:

```bash
#!/bin/bash
# CI/CD test script

# Start services
docker-compose up -d

# Wait for services to be ready
sleep 5

# Verify Mercure
if ! ./scripts/mercure-verify.sh; then
    echo "Mercure verification failed"
    exit 1
fi

# Run end-to-end test
if ! ./scripts/mercure-test.sh 5 Loop php84; then
    echo "Mercure E2E test failed"
    exit 1
fi

echo "All Mercure tests passed!"
```

---

### 5. Event Analysis

Capture and analyze events:

```bash
# Capture events to file (run for 60 seconds)
timeout 60 ./scripts/mercure-listen.sh benchmark/progress raw > events.log &

# Run benchmarks
make run iterations=100

# Wait for capture to complete
wait

# Analyze events
grep '"benchmark.progress"' events.log | wc -l  # Count progress events
grep '"progress":100' events.log  # Find 100% completion
```

---

## Tips & Tricks

### Quick Health Check

```bash
# One-line health check
./scripts/mercure-verify.sh && echo "✅ Mercure is healthy"
```

### Watch Events with Colors

```bash
# Best for monitoring during development
./scripts/mercure-listen.sh benchmark/progress pretty
```

### Count Events by Type

```bash
# Use stats mode to see event distribution
./scripts/mercure-listen.sh benchmark/progress stats
```

### Extract Specific Data

```bash
# Extract only progress percentages
./scripts/mercure-listen.sh benchmark/progress json | jq -r '.progress // empty'

# Extract benchmark names
./scripts/mercure-listen.sh benchmark/progress json | jq -r '.benchmarkName // empty' | sort -u
```

### Test Specific Scenarios

```bash
# Test slow benchmark
./scripts/mercure-test.sh 100 HashWithSha256 php84

# Test multiple versions
for version in php56 php70 php80 php84; do
    ./scripts/mercure-test.sh 5 Loop $version
done
```

---

## Troubleshooting

### Script exits immediately

**Symptom**: `mercure-listen.sh` exits right after starting

**Cause**: No events are being published

**Solution**:
1. Run `./scripts/mercure-verify.sh` to check configuration
2. Check if benchmarks are actually running
3. Check Mercure logs: `docker-compose logs -f mercure`

---

### "jq: command not found"

**Symptom**: Error when using `json` or `pretty` format

**Solution**:
```bash
# Install jq
sudo apt-get install jq  # Debian/Ubuntu
brew install jq          # macOS
```

Or use `raw` format which doesn't require jq.

---

### Permission denied

**Symptom**: `./scripts/mercure-*.sh: Permission denied`

**Solution**:
```bash
chmod +x scripts/mercure-*.sh
```

---

### Docker not running

**Symptom**: `Cannot connect to Docker daemon`

**Solution**:
```bash
# Start Docker
sudo systemctl start docker

# Or start via docker-compose
docker-compose up -d
```

---

## See Also

- [Mercure Practical Guide](../docs/infrastructure/mercure-practical-guide.md) - Detailed debugging and usage
- [Mercure Real-Time Documentation](../docs/infrastructure/mercure-realtime.md) - Architecture and configuration
- [Docker Infrastructure](../docs/infrastructure/docker.md) - Overall Docker setup

---

**Last updated**: 2025-10-22
