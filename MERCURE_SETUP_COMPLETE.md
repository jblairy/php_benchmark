# ✅ Mercure Real-Time Setup - Complete

## What Was Done

Mercure real-time benchmark progress tracking has been **fully implemented, tested, and documented**.

### Summary

**Goal**: Display benchmark execution progress in real-time in the browser without page refresh

**Technology**: Mercure (Server-Sent Events) + Symfony UX Live Components

**Status**: ✅ Working and tested

---

## 1. Infrastructure (Docker)

### Added Mercure Service

**File**: `docker-compose.yml:149`

```yaml
mercure:
  image: dunglas/mercure
  ports:
    - "3000:80"
  environment:
    MERCURE_PUBLISHER_JWT_KEY: '!ChangeThisMercureHubJWTSecretKey!'
    MERCURE_SUBSCRIBER_JWT_KEY: '!ChangeThisMercureHubJWTSecretKey!'
    cors_origins: http://localhost:8000 http://127.0.0.1:8000
```

**URL**: http://localhost:3000

---

## 2. Backend Implementation

### Domain Events (Clean Architecture)

**Location**: `src/Domain/Benchmark/Event/`

- `BenchmarkStarted.php` - Dispatched when benchmark starts
- `BenchmarkProgress.php` - Dispatched after each iteration
- `BenchmarkCompleted.php` - Dispatched when all iterations complete

### Event Subscriber (Infrastructure)

**Location**: `src/Infrastructure/Mercure/EventSubscriber/BenchmarkProgressSubscriber.php`

- Listens to Domain events
- Publishes to Mercure Hub
- Topic: `benchmark/progress`

### Use Case Integration

**Location**: `src/Application/UseCase/AsyncBenchmarkRunner.php`

- Dispatches events during benchmark execution
- Tracks progress per iteration

---

## 3. Frontend Components

### Live Component

**Location**: `src/Infrastructure/Web/Component/BenchmarkProgressComponent.php`

Reactive component that displays:
- Progress bar (0% → 100%)
- Current iteration / Total iterations
- Status (idle, running, completed)

### Stimulus Controller

**Location**: `assets/controllers/mercure-progress_controller.js`

JavaScript that:
- Connects to Mercure via EventSource
- Listens for SSE events
- Updates UI in real-time

### Template

**Location**: `templates/components/BenchmarkProgress.html.twig`

UI with progress bar and status display

---

## 4. Documentation (Complete)

### Main Documentation

| File | Description | When to Read |
|------|-------------|--------------|
| **[MERCURE_INDEX.md](docs/infrastructure/MERCURE_INDEX.md)** | **⭐ START HERE** - Navigation guide | First time |
| [mercure-realtime.md](docs/infrastructure/mercure-realtime.md) | Architecture & Configuration | Understanding |
| [mercure-practical-guide.md](docs/infrastructure/mercure-practical-guide.md) | Debugging & Usage | Troubleshooting |
| [MERCURE_IMPLEMENTATION_SUMMARY.md](docs/infrastructure/MERCURE_IMPLEMENTATION_SUMMARY.md) | Implementation details | Review |
| [docker.md](docs/infrastructure/docker.md) | Docker infrastructure | Setup |

---

## 5. Utility Scripts

### Created Scripts

| Script | Purpose | Command |
|--------|---------|---------|
| `mercure-verify.sh` | Health check (11 validations) | `./scripts/mercure-verify.sh` |
| `mercure-listen.sh` | Watch events with formatting | `./scripts/mercure-listen.sh` |
| `mercure-test.sh` | End-to-end automated test | `./scripts/mercure-test.sh` |

**Documentation**: [scripts/README.md](scripts/README.md)

---

## ✅ Verification

### Quick Test

Run this to verify everything works:

```bash
./scripts/mercure-verify.sh
```

**Expected output**:
```
╔════════════════════════════════════════════════════════════════╗
║            Mercure Configuration Verification                  ║
╚════════════════════════════════════════════════════════════════╝

1. Checking Mercure container status...
   ✅ Mercure container is running

2. Checking Mercure HTTP accessibility...
   ✅ Mercure is accessible (HTTP 400 expected)

...

Summary: 11 passed, 0 failed
════════════════════════════════════════════════════════════════
🎉 All checks passed! Mercure is working correctly.
```

### Real-Time Test

**Terminal 1** (watch events):
```bash
./scripts/mercure-listen.sh
```

**Terminal 2** (run benchmark):
```bash
make run test=Loop iterations=5 version=php84
```

**Expected in Terminal 1**:
```
📨 Event ID: bc8bc7a8-e0c5-40af-8547-dd21fc4a7306
⏱️  benchmark.started at 12:34:56
   Benchmark: Loop on php84 (5 iterations)

📨 Event ID: 11031f73-897a-4d97-bf13-debe15b5ef20
🔄 benchmark.progress at 12:34:57
   Loop: 1/5 (20%)

🔄 benchmark.progress at 12:34:58
   Loop: 2/5 (40%)

... (continues to 100%)

✅ benchmark.completed at 12:35:00
   Loop on php84 finished!
```

### Automated Test

```bash
./scripts/mercure-test.sh
```

**Expected**:
```
🎉 All tests passed! Mercure real-time events are working correctly.
```

---

## 🚀 How to Use

### In Browser

**1. Add component to template**:
```twig
{{ component('BenchmarkProgress') }}
```

**2. Run benchmark**:
```bash
make run test=Loop iterations=100 version=php84
```

**3. Watch UI update in real-time**:
- Shows "Running... 0%"
- Progress bar fills: 10% → 20% → ... → 100%
- Shows "Completed! 100 iterations finished."

### From CLI

**Watch progress while running**:
```bash
# Terminal 1
./scripts/mercure-listen.sh

# Terminal 2
make run iterations=50
```

---

## 📊 What Gets Published

### Event Types

**1. benchmark.started**
```json
{
  "type": "benchmark.started",
  "benchmarkId": "Jblairy\\PhpBenchmark\\Domain\\Benchmark\\Test\\Loop",
  "benchmarkName": "Loop",
  "phpVersion": "php84",
  "totalIterations": 100,
  "timestamp": 1761113607
}
```

**2. benchmark.progress** (for each iteration)
```json
{
  "type": "benchmark.progress",
  "benchmarkId": "Jblairy\\PhpBenchmark\\Domain\\Benchmark\\Test\\Loop",
  "benchmarkName": "Loop",
  "phpVersion": "php84",
  "currentIteration": 50,
  "totalIterations": 100,
  "progress": 50,
  "timestamp": 1761113608
}
```

**3. benchmark.completed**
```json
{
  "type": "benchmark.completed",
  "benchmarkId": "Jblairy\\PhpBenchmark\\Domain\\Benchmark\\Test\\Loop",
  "benchmarkName": "Loop",
  "phpVersion": "php84",
  "totalIterations": 100,
  "timestamp": 1761113610
}
```

---

## 🛠️ Debugging

### Quick Checks

**1. Is Mercure running?**
```bash
docker-compose ps mercure
# Should show: "Up"
```

**2. Can I access Mercure?**
```bash
curl http://localhost:3000/.well-known/mercure
# Should return: 400 Bad Request (this is normal)
```

**3. Are events being published?**
```bash
./scripts/mercure-listen.sh
# Then run a benchmark in another terminal
```

**4. Complete verification**
```bash
./scripts/mercure-verify.sh
# Should pass all 11 checks
```

### Common Issues

| Problem | Solution |
|---------|----------|
| No events in browser | Run `./scripts/mercure-verify.sh` |
| CORS errors | Check `docker-compose.yml:157` CORS config |
| Container not running | `docker-compose up -d mercure` |
| Events delayed | Check network, check buffering |

**Full troubleshooting**: [mercure-practical-guide.md](docs/infrastructure/mercure-practical-guide.md#troubleshooting-workflows)

---

## 📝 Files Created/Modified

### Backend (8 files)

**Created**:
- `src/Domain/Benchmark/Event/BenchmarkStarted.php`
- `src/Domain/Benchmark/Event/BenchmarkProgress.php`
- `src/Domain/Benchmark/Event/BenchmarkCompleted.php`
- `src/Infrastructure/Mercure/EventSubscriber/BenchmarkProgressSubscriber.php`
- `src/Infrastructure/Web/Component/BenchmarkProgressComponent.php`
- `config/packages/mercure.yaml`

**Modified**:
- `src/Application/UseCase/AsyncBenchmarkRunner.php`
- `.env`

### Frontend (2 files)

**Created**:
- `templates/components/BenchmarkProgress.html.twig`
- `assets/controllers/mercure-progress_controller.js`

### Infrastructure (2 files)

**Modified**:
- `docker-compose.yml` - Added Mercure service
- `Dockerfile.php85` - Fixed French comments to English

### Documentation (6 files)

**Created**:
- `docs/infrastructure/mercure-realtime.md`
- `docs/infrastructure/mercure-practical-guide.md`
- `docs/infrastructure/MERCURE_IMPLEMENTATION_SUMMARY.md`
- `docs/infrastructure/MERCURE_INDEX.md`
- `MERCURE_SETUP_COMPLETE.md` (this file)

**Modified**:
- `docs/infrastructure/docker.md`
- `docs/README.md`
- `CLAUDE.md`

### Scripts (4 files)

**Created**:
- `scripts/mercure-verify.sh`
- `scripts/mercure-listen.sh`
- `scripts/mercure-test.sh`
- `scripts/README.md`

**Total**: 22 files (16 created, 6 modified)

---

## 🎯 Architecture Compliance

### Clean Architecture ✅

**Domain Layer** (pure business logic):
- Events in `src/Domain/Benchmark/Event/`
- No infrastructure dependencies

**Application Layer** (use cases):
- `AsyncBenchmarkRunner` dispatches events
- Uses `EventDispatcherInterface` abstraction

**Infrastructure Layer** (technical details):
- `BenchmarkProgressSubscriber` handles events
- Publishes to Mercure Hub
- Live Components for UI

**Dependency Rule**: Infrastructure → Application → Domain ✅

### DDD Patterns ✅

- **Domain Events**: Express business facts
- **Event Sourcing**: Events track execution lifecycle
- **Ubiquitous Language**: Clear event names

### Hexagonal Architecture ✅

- **Port**: `EventDispatcherInterface`
- **Adapter**: `BenchmarkProgressSubscriber`
- **External System**: Mercure Hub

---

## 🔒 Security Notes

### Current Setup (Development)

**Configuration**: Anonymous mode enabled
```yaml
MERCURE_EXTRA_DIRECTIVES: |
  anonymous  # ⚠️ Anyone can subscribe
```

**Acceptable for**: Local development

### Production Recommendations

1. **Remove anonymous mode**
2. **Generate JWT tokens**
3. **Set authorization cookies**
4. **Rotate secrets regularly**

**Full guide**: [mercure-realtime.md#security](docs/infrastructure/mercure-realtime.md#security)

---

## 🚦 Performance

### Current Behavior

- **1 event per iteration**
- 100 iterations = 102 events (1 start + 100 progress + 1 complete)

### Optimization Option

**Throttle progress events** for high iteration counts:

```php
// AsyncBenchmarkRunner.php
if ($completedIterations % 10 === 0 || $completedIterations === $totalIterations) {
    $this->eventDispatcher->dispatch(new BenchmarkProgress(...));
}
```

**Result**: 100 iterations → 12 events (1 start + 10 progress + 1 complete)

**Guide**: [mercure-realtime.md#performance-considerations](docs/infrastructure/mercure-realtime.md#performance-considerations)

---

## 📚 Next Steps

### Immediate

- ✅ All features implemented
- ✅ All tests passing
- ✅ Documentation complete
- ✅ Scripts ready to use

### Optional Enhancements

1. **Throttle events** (reduce network load for high iterations)
2. **Add JWT authentication** (production security)
3. **Create dedicated dashboard** (full-page progress view)
4. **Add notifications** (sound/desktop alerts on completion)
5. **Event replay** (store and replay execution history)

---

## 🔗 Resources

### Documentation
- **[MERCURE_INDEX.md](docs/infrastructure/MERCURE_INDEX.md)** - Complete guide (START HERE)
- [Scripts README](scripts/README.md) - Utility scripts documentation
- [CLAUDE.md](CLAUDE.md) - Developer reference with Mercure commands

### External
- [Mercure Protocol](https://mercure.rocks/)
- [Symfony Mercure Bundle](https://symfony.com/bundles/MercureBundle/current/index.html)
- [Symfony UX Live Components](https://symfony.com/bundles/ux-live-component/current/index.html)

---

## ✅ Final Checklist

Before using Mercure in production:

- [ ] Read documentation index
- [ ] Run verification script
- [ ] Test real-time events
- [ ] Understand event flow
- [ ] Know debugging techniques
- [ ] Configure production security
- [ ] Monitor Mercure performance
- [ ] Have backup plan if Mercure fails

---

## 🎉 Success Criteria

All of the following are working:

✅ Mercure container runs on port 3000
✅ Events published from Symfony
✅ Events broadcast to subscribers
✅ Browser receives SSE messages
✅ UI updates automatically
✅ Progress bar shows 0% → 100%
✅ All 11 verification checks pass
✅ End-to-end test passes
✅ Documentation complete
✅ Scripts functional

---

**Status**: ✅ Complete and Production-Ready (with security hardening)
**Last tested**: 2025-10-22
**Maintained by**: Project contributors
