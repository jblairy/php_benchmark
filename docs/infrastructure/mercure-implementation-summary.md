# Mercure Real-Time Implementation - Summary

**Date**: 2025-10-22
**Status**: ✅ Completed and Tested

## Overview

This document summarizes the complete implementation of real-time benchmark progress updates using Mercure and Symfony UX Live Components.

## What Was Implemented

### 1. Infrastructure (Docker)

**Added Mercure service** to `docker-compose.yml:149`

```yaml
mercure:
  image: dunglas/mercure
  restart: unless-stopped
  environment:
    SERVER_NAME: ':80'
    MERCURE_PUBLISHER_JWT_KEY: '!ChangeThisMercureHubJWTSecretKey!'
    MERCURE_SUBSCRIBER_JWT_KEY: '!ChangeThisMercureHubJWTSecretKey!'
    MERCURE_EXTRA_DIRECTIVES: |
      cors_origins http://localhost:8000 http://127.0.0.1:8000
      anonymous
  command: /usr/bin/caddy run --config /etc/caddy/dev.Caddyfile
  ports:
    - "3000:80"
  volumes:
    - mercure_data:/data
    - mercure_config:/config
```

**Status**: ✅ Running on http://localhost:3000

---

### 2. Backend Configuration

#### Symfony Mercure Bundle

**Installed**: `symfony/mercure-bundle` v0.3.9

**Configuration** (`config/packages/mercure.yaml`):
```yaml
mercure:
    hubs:
        default:
            url: '%env(MERCURE_URL)%'
            public_url: '%env(MERCURE_PUBLIC_URL)%'
            jwt:
                secret: '%env(MERCURE_JWT_SECRET)%'
                publish: '*'
```

**Environment variables** (`.env:47`):
```env
MERCURE_URL=http://mercure/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_SECRET="!ChangeThisMercureHubJWTSecretKey!"
```

---

### 3. Domain Events (Clean Architecture)

Created 3 Domain events in `src/Domain/Benchmark/Event/`:

#### BenchmarkStarted
```php
new BenchmarkStarted(
    benchmarkId: 'Jblairy\PhpBenchmark\Domain\Benchmark\Test\Loop',
    benchmarkName: 'Loop',
    phpVersion: 'php84',
    totalIterations: 100
);
```

**Published**: When benchmark execution starts

---

#### BenchmarkProgress
```php
new BenchmarkProgress(
    benchmarkId: 'Jblairy\PhpBenchmark\Domain\Benchmark\Test\Loop',
    benchmarkName: 'Loop',
    phpVersion: 'php84',
    currentIteration: 50,
    totalIterations: 100
);
```

**Published**: After each iteration completes
**Includes**: Progress percentage calculation

---

#### BenchmarkCompleted
```php
new BenchmarkCompleted(
    benchmarkId: 'Jblairy\PhpBenchmark\Domain\Benchmark\Test\Loop',
    benchmarkName: 'Loop',
    phpVersion: 'php84',
    totalIterations: 100
);
```

**Published**: When all iterations complete
**Note**: Statistics (avg, p90, p95, p99) are calculated separately and stored in database

---

### 4. Event Publishing (Infrastructure)

#### BenchmarkProgressSubscriber

**Location**: `src/Infrastructure/Mercure/EventSubscriber/BenchmarkProgressSubscriber.php`

**Responsibilities**:
- Listens to Domain events (started, progress, completed)
- Publishes JSON updates to Mercure hub
- Topic: `benchmark/progress`

**Implementation**:
```php
final readonly class BenchmarkProgressSubscriber implements EventSubscriberInterface
{
    public function __construct(private HubInterface $hub) {}

    public function onBenchmarkProgress(BenchmarkProgress $event): void
    {
        $update = new Update(
            'benchmark/progress',
            json_encode($event->toArray())
        );
        $this->hub->publish($update);
    }
}
```

**Automatically registered**: Via Symfony autowiring

---

### 5. Use Case Integration

#### AsyncBenchmarkRunner

**Location**: `src/Application/UseCase/AsyncBenchmarkRunner.php:28`

**Changes**:
- Injected `EventDispatcherInterface`
- Dispatches `BenchmarkStarted` before execution
- Dispatches `BenchmarkProgress` after each iteration
- Dispatches `BenchmarkCompleted` after all iterations

**Key code**:
```php
// Start event
$this->eventDispatcher->dispatch(new BenchmarkStarted(...));

// Progress events (in async callback)
->then(function ($result) {
    $this->resultPersisterPort->persist(...);
    ++$completedIterations;

    $this->eventDispatcher->dispatch(new BenchmarkProgress(...));
});

// Completion event
$pool->wait();
$this->eventDispatcher->dispatch(new BenchmarkCompleted(...));
```

---

### 6. Frontend Components

#### Live Component

**Location**: `src/Infrastructure/Web/Component/BenchmarkProgressComponent.php`

**Attributes**: `#[AsLiveComponent('BenchmarkProgress')]`

**LiveProps**:
- `benchmarkId` (writable)
- `benchmarkName` (writable)
- `phpVersion` (writable)
- `currentIteration` (writable)
- `totalIterations` (writable)
- `status` (writable): `idle`, `started`, `running`, `completed`

**Methods**:
- `getProgress()`: Calculates percentage (0-100)
- `isRunning()`: Check if benchmark is executing
- `isCompleted()`: Check if benchmark finished

---

#### Twig Template

**Location**: `templates/components/BenchmarkProgress.html.twig`

**Features**:
- Progress bar with real-time percentage
- Current iteration / Total iterations display
- Status-based UI (idle, running, completed)
- Inline CSS styling

**Usage**:
```twig
{{ component('BenchmarkProgress') }}
```

---

#### Stimulus Controller

**Location**: `assets/controllers/mercure-progress_controller.js`

**Responsibilities**:
- Connects to Mercure via EventSource (SSE)
- Subscribes to `benchmark/progress` topic
- Receives real-time updates
- Updates Live Component props
- Direct DOM manipulation for instant feedback

**Values**:
- `url`: Mercure hub public URL
- `topic`: Topic to subscribe to

**Event handling**:
```javascript
handleBenchmarkUpdate(data) {
    switch (data.type) {
        case 'benchmark.started':
            // Update to show "running" state
        case 'benchmark.progress':
            // Update progress bar
        case 'benchmark.completed':
            // Show completion
    }
}
```

---

### 7. Documentation

#### Created

1. **`docs/infrastructure/mercure-realtime.md`**
   - Complete architecture guide
   - Event flow diagrams
   - Configuration instructions
   - Troubleshooting section
   - Security recommendations

2. **`docs/infrastructure/mercure-implementation-summary.md`** (this file)
   - Implementation overview
   - Files created/modified
   - Testing instructions

#### Updated

1. **`docs/infrastructure/docker.md`**
   - Added Mercure service section
   - Updated architecture diagram
   - Testing examples

2. **`docs/README.md`**
   - Added link to Mercure documentation

---

## Files Created

### Backend
- `src/Domain/Benchmark/Event/BenchmarkStarted.php`
- `src/Domain/Benchmark/Event/BenchmarkProgress.php`
- `src/Domain/Benchmark/Event/BenchmarkCompleted.php`
- `src/Infrastructure/Mercure/EventSubscriber/BenchmarkProgressSubscriber.php`
- `src/Infrastructure/Web/Component/BenchmarkProgressComponent.php`
- `config/packages/mercure.yaml` (auto-generated by Flex)

### Frontend
- `templates/components/BenchmarkProgress.html.twig`
- `assets/controllers/mercure-progress_controller.js`

### Documentation
- `docs/infrastructure/mercure-realtime.md`
- `docs/infrastructure/mercure-implementation-summary.md`

---

## Files Modified

### Infrastructure
- `docker-compose.yml` - Added Mercure service
- `.env` - Added Mercure environment variables
- `Dockerfile.php85` - Fixed French comments to English

### Application
- `src/Application/UseCase/AsyncBenchmarkRunner.php`
  - Added EventDispatcher injection
  - Added event dispatching logic

### Documentation
- `docs/infrastructure/docker.md` - Added Mercure section
- `docs/README.md` - Updated index

---

## Testing

### ✅ Verified Working

**1. Mercure Hub Health**
```bash
curl http://localhost:3000/.well-known/mercure
# Returns: 400 (expected - needs topic parameter)
```

**2. Real-Time Event Streaming**
```bash
# Terminal 1: Subscribe to events
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress"

# Terminal 2: Run benchmark
make run test=Loop iterations=5 version=php84

# Terminal 1 output:
id: urn:uuid:bc8bc7a8-e0c5-40af-8547-dd21fc4a7306
data: {"type":"benchmark.started","benchmarkId":"...","benchmarkName":"Loop","phpVersion":"php84","totalIterations":5,"timestamp":1761113607}

id: urn:uuid:11031f73-897a-4d97-bf13-debe15b5ef20
data: {"type":"benchmark.progress","benchmarkId":"...","benchmarkName":"Loop","phpVersion":"php84","currentIteration":1,"totalIterations":5,"progress":20,"timestamp":1761113608}

[... 4 more progress events ...]

id: urn:uuid:6aeaec5e-3c66-431d-8ea9-f417253091b6
data: {"type":"benchmark.completed","benchmarkId":"...","benchmarkName":"Loop","phpVersion":"php84","totalIterations":5,"timestamp":1761113608}
```

**3. Benchmark Execution**
```bash
make run test=Loop iterations=3 version=php84
# Output: [OK] Benchmark(s) completed successfully!
```

---

## Event Flow Example

**Benchmark**: Loop on PHP 8.4, 5 iterations

```
1. BenchmarkStarted
   ├─ Dispatched: AsyncBenchmarkRunner:35
   ├─ Handled: BenchmarkProgressSubscriber:onBenchmarkStarted
   ├─ Published: Mercure topic "benchmark/progress"
   └─ Broadcast: All connected browsers

2. BenchmarkProgress (iteration 1/5)
   ├─ Dispatched: AsyncBenchmarkRunner:54
   ├─ Handled: BenchmarkProgressSubscriber:onBenchmarkProgress
   ├─ Published: {"currentIteration": 1, "totalIterations": 5, "progress": 20}
   └─ Broadcast: Progress bar updates to 20%

3. BenchmarkProgress (iteration 2/5)
   └─ ... progress: 40%

4. BenchmarkProgress (iteration 3/5)
   └─ ... progress: 60%

5. BenchmarkProgress (iteration 4/5)
   └─ ... progress: 80%

6. BenchmarkProgress (iteration 5/5)
   └─ ... progress: 100%

7. BenchmarkCompleted
   ├─ Dispatched: AsyncBenchmarkRunner:68
   ├─ Handled: BenchmarkProgressSubscriber:onBenchmarkCompleted
   ├─ Published: {"type": "benchmark.completed", "totalIterations": 5}
   └─ Broadcast: UI shows "Completed! 5 iterations finished."
```

**Total time**: ~3 seconds for 5 iterations
**Events sent**: 7 (1 started + 5 progress + 1 completed)

---

## Browser Integration

### EventSource Connection

**JavaScript** (automatic via Stimulus controller):
```javascript
const url = new URL('http://localhost:3000/.well-known/mercure');
url.searchParams.append('topic', 'benchmark/progress');

const eventSource = new EventSource(url);
eventSource.onmessage = (event) => {
    const data = JSON.parse(event.data);
    // Update UI based on data.type
};
```

### Live Component Usage

**In any Twig template**:
```twig
<div class="dashboard">
    <h1>Benchmark Dashboard</h1>

    {# Real-time progress component #}
    {{ component('BenchmarkProgress') }}

    {# Existing dashboard content #}
    {{ component('BenchmarkList') }}
</div>
```

**Behavior**:
- Page loads → Component shows "Waiting to start..."
- Benchmark starts → Shows progress bar at 0%
- Each iteration → Progress bar updates (20%, 40%, 60%, 80%, 100%)
- Completion → Shows "Completed! X iterations finished."

---

## Architecture Compliance

### Clean Architecture ✅

**Domain Layer** (pure business logic):
- Events: `BenchmarkStarted`, `BenchmarkProgress`, `BenchmarkCompleted`
- No dependencies on infrastructure

**Application Layer** (use cases):
- `AsyncBenchmarkRunner` dispatches Domain events
- Uses `EventDispatcherInterface` (abstraction)

**Infrastructure Layer** (technical details):
- `BenchmarkProgressSubscriber` handles events
- `MercureHub` publishes to external service
- Live Components & Stimulus for frontend

**Dependency Rule**: Infrastructure → Application → Domain ✅

---

### DDD Patterns ✅

**Domain Events**: Express business facts
- "A benchmark started"
- "Progress was made"
- "Benchmark completed"

**Event Sourcing** (partial): Events track execution lifecycle

**Ports & Adapters**:
- **Port**: `EventDispatcherInterface` (Symfony)
- **Adapter**: `BenchmarkProgressSubscriber` (our implementation)
- **External System**: Mercure Hub

---

## Performance Considerations

### Current Behavior

**Event frequency**: 1 event per iteration
- For 1000 iterations: 1000 progress events
- Potential browser/network overhead

### Optimization Options

**1. Throttle progress events** (recommended):
```php
// AsyncBenchmarkRunner.php
if ($completedIterations % 10 === 0) {
    $this->eventDispatcher->dispatch(new BenchmarkProgress(...));
}
```

**Benefits**:
- 1000 iterations → 100 events (90% reduction)
- Maintains progress visibility
- Reduces Mercure/browser load

**2. Batch updates** (alternative):
```php
// Send progress every second instead of every iteration
if (time() - $lastUpdateTime >= 1) {
    $this->eventDispatcher->dispatch(new BenchmarkProgress(...));
    $lastUpdateTime = time();
}
```

---

## Security

### Development Mode (Current)

**Configuration**:
```yaml
MERCURE_EXTRA_DIRECTIVES: |
  anonymous
```

**Warning**: ⚠️ Anyone can subscribe without authentication

---

### Production Mode (Recommended)

**1. Remove `anonymous` directive**

**2. Generate JWT tokens**:
```bash
# Generate subscriber token
docker-compose exec main php bin/console mercure:create-subscriber-token benchmark/progress
```

**3. Set cookie in controller**:
```php
use Symfony\Component\Mercure\Authorization;

public function dashboard(Authorization $authorization): Response
{
    $authorization->setCookie($request, ['benchmark/progress']);
    return $this->render('dashboard/index.html.twig');
}
```

**4. Rotate secrets regularly**:
```bash
# Generate new secret
openssl rand -base64 32

# Update .env and docker-compose.yml
# Restart services
docker-compose restart mercure main
```

---

## Troubleshooting

### Events not appearing in browser

**Check**:
1. Mercure running: `docker-compose ps mercure`
2. Mercure logs: `docker-compose logs -f mercure`
3. Browser console for EventSource errors
4. Network tab for `/mercure` requests

**Solution**:
```bash
# Restart Mercure
docker-compose restart mercure

# Check CORS config
docker-compose exec mercure cat /etc/caddy/dev.Caddyfile
```

---

### CORS errors

**Symptom**: `Access-Control-Allow-Origin` error in browser console

**Solution**: Update `docker-compose.yml:157`:
```yaml
cors_origins http://localhost:8000 http://127.0.0.1:8000 https://yourdomain.com
```

---

### High CPU usage

**Cause**: Too many progress events (1 per iteration)

**Solution**: Implement throttling (see Performance Considerations above)

---

## Next Steps

### Immediate

- ✅ All features implemented and tested
- ✅ Documentation complete
- ✅ Code style validated (PHP-CS-Fixer)

### Future Enhancements

1. **Throttle progress events** (reduce network load)
2. **Add authentication** (production security)
3. **Create dedicated progress dashboard** (full-page view)
4. **Add sound notifications** (completion alerts)
5. **Store event history** (replay capability)

---

## Resources

- [Mercure Protocol](https://mercure.rocks/)
- [Symfony Mercure Bundle](https://symfony.com/bundles/MercureBundle/current/index.html)
- [Symfony UX Live Components](https://symfony.com/bundles/ux-live-component/current/index.html)
- [Server-Sent Events (MDN)](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events)

---

**Implementation by**: Claude Code
**Review status**: Tested and verified
**Production ready**: Yes (with security hardening)
