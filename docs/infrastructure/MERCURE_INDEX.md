# Mercure Documentation Index

Complete guide to real-time benchmark progress with Mercure.

## 📚 Documentation Structure

```
docs/infrastructure/
├── mercure-realtime.md              # Architecture & Configuration (Complete)
├── mercure-practical-guide.md       # Debugging & Usage (Hands-on)
├── MERCURE_IMPLEMENTATION_SUMMARY.md # What was built (Summary)
└── MERCURE_INDEX.md                 # This file (Navigation)

scripts/
├── mercure-verify.sh                # Configuration checker
├── mercure-listen.sh                # Event listener with formatting
├── mercure-test.sh                  # End-to-end automated test
└── README.md                        # Scripts documentation
```

## 🎯 Quick Start

**I'm new to Mercure**:
1. Read: [mercure-realtime.md](mercure-realtime.md) - Section "Overview"
2. Run: `./scripts/mercure-verify.sh`
3. Test: `./scripts/mercure-test.sh`

**I want to see it working**:
1. Terminal 1: `./scripts/mercure-listen.sh`
2. Terminal 2: `make run test=Loop iterations=5 version=php84`
3. Watch events appear in Terminal 1 in real-time!

**I have problems**:
1. Read: [mercure-practical-guide.md](mercure-practical-guide.md) - Section "Troubleshooting Workflows"
2. Run: `./scripts/mercure-verify.sh` to diagnose issues
3. Check logs: `docker-compose logs -f mercure`

## 📖 Documentation Guide

### 1. Architecture & Configuration
**File**: [mercure-realtime.md](mercure-realtime.md)

**Read this if**:
- You want to understand how Mercure works
- You need to configure Mercure
- You're implementing similar features

**Contents**:
- Complete architecture diagrams
- Event flow explanation
- Configuration reference
- Security recommendations
- Performance considerations

**Best sections**:
- [Architecture](mercure-realtime.md#architecture) - Visual diagrams
- [Configuration](mercure-realtime.md#configuration) - Environment setup
- [Event Flow](mercure-realtime.md#event-flow) - Step-by-step execution

---

### 2. Debugging & Practical Usage
**File**: [mercure-practical-guide.md](mercure-practical-guide.md)

**Read this if**:
- Events are not appearing in browser
- You want to verify Mercure is working
- You need debugging techniques
- You want practical examples

**Contents**:
- 9 debugging techniques
- Complete verification checklist
- 5 practical use cases
- Useful commands by category
- Troubleshooting workflows

**Best sections**:
- [How to Debug Mercure](mercure-practical-guide.md#how-to-debug-mercure) - 9 techniques
- [Common Use Cases](mercure-practical-guide.md#common-use-cases) - Real examples
- [Useful Commands](mercure-practical-guide.md#useful-commands) - Command reference

---

### 3. Implementation Summary
**File**: [MERCURE_IMPLEMENTATION_SUMMARY.md](MERCURE_IMPLEMENTATION_SUMMARY.md)

**Read this if**:
- You want a high-level overview
- You need to know what was built
- You're reviewing the implementation

**Contents**:
- What was implemented
- Files created/modified
- Testing results
- Performance notes
- Future enhancements

**Best sections**:
- [What Was Implemented](MERCURE_IMPLEMENTATION_SUMMARY.md#what-was-implemented)
- [Testing](MERCURE_IMPLEMENTATION_SUMMARY.md#testing)
- [Architecture Compliance](MERCURE_IMPLEMENTATION_SUMMARY.md#architecture-compliance)

---

### 4. Docker Infrastructure
**File**: [docker.md](docker.md#real-time-updates)

**Read this if**:
- You need Docker-specific Mercure info
- You're setting up the infrastructure
- You want to understand service architecture

**Contents**:
- Mercure service configuration
- Docker Compose setup
- Port mappings
- Volume configuration

**Best section**:
- [Real-Time Updates](docker.md#real-time-updates) - Mercure service details

---

## 🛠️ Scripts Guide

### Quick Reference

| Script | Purpose | Usage |
|--------|---------|-------|
| **mercure-verify.sh** | Health check | `./scripts/mercure-verify.sh` |
| **mercure-listen.sh** | Watch events | `./scripts/mercure-listen.sh [topic] [format]` |
| **mercure-test.sh** | E2E test | `./scripts/mercure-test.sh [iterations] [test] [version]` |

### mercure-verify.sh
✅ **Configuration checker** - Verifies Mercure setup

**When to use**:
- After initial setup
- When events stop working
- Before debugging

**What it checks** (15+ validations):
- Container status
- Port accessibility
- Environment variables
- Event subscriber registration
- CORS configuration
- Log errors

**Example**:
```bash
./scripts/mercure-verify.sh

# Output:
# ╔════════════════════════════════════════════════════════════════╗
# ║            Mercure Configuration Verification                  ║
# ╚════════════════════════════════════════════════════════════════╝
#
# 1. Checking Mercure container status...
#    ✅ Mercure container is running
# ...
# Summary: 15 passed, 0 failed
# 🎉 All checks passed!
```

---

### mercure-listen.sh
👂 **Event listener** - Watch real-time events with formatting

**When to use**:
- Debug event publishing
- Monitor benchmark execution
- Verify events are sent correctly

**Formats**:
- `pretty` - Colored, formatted output (default)
- `json` - JSON only (requires jq)
- `raw` - Raw SSE format
- `stats` - Event count statistics

**Examples**:

**Watch with pretty formatting**:
```bash
./scripts/mercure-listen.sh

# Output:
# 📨 Event ID: bc8bc7a8-e0c5-40af-8547-dd21fc4a7306
# ⏱️  benchmark.started at 12:34:56
#    Benchmark: Loop on php84 (5 iterations)
#
# 🔄 benchmark.progress at 12:34:57
#    Loop: 1/5 (20%)
```

**JSON format** (for parsing):
```bash
./scripts/mercure-listen.sh "" json

# Output:
# {
#   "type": "benchmark.progress",
#   "benchmarkName": "Loop",
#   "currentIteration": 1,
#   "totalIterations": 5,
#   "progress": 20
# }
```

**Statistics mode**:
```bash
./scripts/mercure-listen.sh "" stats

# Output (updates in real-time):
# Started: 1 | Progress: 5 | Completed: 1
```

---

### mercure-test.sh
🧪 **End-to-end test** - Automated validation

**When to use**:
- Verify complete workflow
- CI/CD pipeline testing
- Regression testing

**What it does**:
1. Starts event listener in background
2. Runs benchmark
3. Captures all events
4. Validates event counts
5. Shows results

**Examples**:

**Quick test** (3 iterations):
```bash
./scripts/mercure-test.sh

# Output:
# ╔════════════════════════════════════════════════════════════════╗
# ║              Mercure End-to-End Test                           ║
# ╚════════════════════════════════════════════════════════════════╝
#
# Events received:
#   📨 Total events: 5
#   🚀 Started: 1
#   🔄 Progress: 3
#   ✅ Completed: 1
#
# 🎉 All tests passed!
```

**Custom test**:
```bash
./scripts/mercure-test.sh 10 Loop php84
# 10 iterations, Loop benchmark, PHP 8.4
```

---

## 🚀 Common Workflows

### Initial Setup

```bash
# 1. Start services
docker-compose up -d

# 2. Verify Mercure
./scripts/mercure-verify.sh

# 3. Test end-to-end
./scripts/mercure-test.sh

# 4. If all passes, you're ready!
```

---

### Development Workflow

```bash
# Terminal 1: Watch events
./scripts/mercure-listen.sh

# Terminal 2: Run benchmarks
make run test=Loop iterations=10 version=php84

# Terminal 1 shows real-time progress
```

---

### Debugging Workflow

```bash
# Step 1: Verify configuration
./scripts/mercure-verify.sh

# Step 2: Check if events are published
./scripts/mercure-listen.sh "" raw &
make run test=Loop iterations=3 version=php84

# Step 3: If no events, check logs
docker-compose logs -f mercure
docker-compose logs -f main

# Step 4: Check event subscriber
docker-compose exec main php bin/console debug:event-dispatcher | grep Benchmark
```

---

### CI/CD Integration

```yaml
# .github/workflows/test.yml
jobs:
  test-mercure:
    runs-on: ubuntu-latest
    steps:
      - name: Start services
        run: docker-compose up -d

      - name: Verify Mercure
        run: ./scripts/mercure-verify.sh

      - name: Run E2E test
        run: ./scripts/mercure-test.sh 5 Loop php84

      - name: Check results
        run: |
          if [ $? -eq 0 ]; then
            echo "✅ Mercure tests passed"
          else
            echo "❌ Mercure tests failed"
            exit 1
          fi
```

---

## 🔍 Troubleshooting Index

### By Symptom

| Symptom | Check | Fix |
|---------|-------|-----|
| No events in browser | [Debug #1](mercure-practical-guide.md#1-check-mercure-service-is-running) | `./scripts/mercure-verify.sh` |
| CORS errors | [Debug #7](mercure-practical-guide.md#7-check-mercure-logs-for-errors) | Update `docker-compose.yml:157` |
| Events delayed | [Troubleshooting](mercure-practical-guide.md#problem-events-are-delayed-or-batched) | Check network/buffering |
| Too many events | [Troubleshooting](mercure-practical-guide.md#problem-too-many-events-high-cpu) | Throttle events |
| Container not starting | [Docker Guide](docker.md#troubleshooting) | `docker-compose logs mercure` |

### By Component

| Component | Documentation | Debug Command |
|-----------|---------------|---------------|
| Mercure Hub | [Docker](docker.md#mercure-service) | `docker-compose logs mercure` |
| Event Publishing | [Architecture](mercure-realtime.md#event-subscriber) | Check subscriber logs |
| Browser Connection | [Practical Guide](mercure-practical-guide.md#8-test-with-browser-devtools) | Browser DevTools Network tab |
| Configuration | [Configuration](mercure-realtime.md#configuration) | `./scripts/mercure-verify.sh` |

---

## 📊 Event Reference

### Event Types

| Type | When | Data Included |
|------|------|---------------|
| `benchmark.started` | Benchmark execution begins | benchmarkId, benchmarkName, phpVersion, totalIterations |
| `benchmark.progress` | Each iteration completes | currentIteration, totalIterations, progress (%) |
| `benchmark.completed` | All iterations done | totalIterations, timestamp |

### Topics

| Topic | Events | Usage |
|-------|--------|-------|
| `benchmark/progress` | All 3 event types | Main topic for progress tracking |
| `benchmark/results` | Only completed events | Final results (not used currently) |

### Event Structure

```json
{
  "type": "benchmark.progress",
  "benchmarkId": "Jblairy\\PhpBenchmark\\Domain\\Benchmark\\Test\\Loop",
  "benchmarkName": "Loop",
  "phpVersion": "php84",
  "currentIteration": 5,
  "totalIterations": 10,
  "progress": 50,
  "timestamp": 1761113607
}
```

---

## 🔗 Quick Links

### Documentation
- [Complete Architecture Guide](mercure-realtime.md)
- [Debugging & Practical Usage](mercure-practical-guide.md)
- [Implementation Summary](MERCURE_IMPLEMENTATION_SUMMARY.md)
- [Docker Setup](docker.md#real-time-updates)
- [Scripts Documentation](../../scripts/README.md)

### Code
- Events: `src/Domain/Benchmark/Event/`
- Subscriber: `src/Infrastructure/Mercure/EventSubscriber/BenchmarkProgressSubscriber.php`
- Use Case: `src/Application/UseCase/AsyncBenchmarkRunner.php`
- Live Component: `src/Infrastructure/Web/Component/BenchmarkProgressComponent.php`

### External Resources
- [Mercure Protocol](https://mercure.rocks/)
- [Symfony Mercure Bundle](https://symfony.com/bundles/MercureBundle/current/index.html)
- [Server-Sent Events (MDN)](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events)

---

## ✅ Checklist

### For Developers

- [ ] Read architecture overview
- [ ] Understand event flow
- [ ] Run verification script
- [ ] Test with listener script
- [ ] Know how to debug

### For Operations

- [ ] Verify Mercure is running
- [ ] Check CORS configuration
- [ ] Monitor Mercure logs
- [ ] Know troubleshooting steps
- [ ] Have backup plan if Mercure fails

### For Testing

- [ ] Run automated E2E test
- [ ] Verify all event types
- [ ] Check event counts
- [ ] Test error scenarios
- [ ] Performance test with many iterations

---

**Last updated**: 2025-10-22
**Status**: Complete and tested
**Maintained by**: Project contributors
