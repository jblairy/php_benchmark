# Mercure Real-Time Updates

This document explains how real-time benchmark progress updates work using Mercure and Live Components.

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Components](#components)
4. [Event Flow](#event-flow)
5. [Configuration](#configuration)
6. [Usage](#usage)
7. [Troubleshooting](#troubleshooting)

## Overview

The PHP Benchmark project uses **Mercure** for real-time Server-Sent Events (SSE) to display benchmark progress as it happens. When a benchmark runs, the backend publishes progress updates to Mercure, and the frontend automatically receives and displays them using Live Components and Stimulus controllers.

**Key benefits**:
- ✅ Real-time progress updates without page refresh
- ✅ Live percentage progress bars
- ✅ Instant result display when benchmarks complete
- ✅ Support for concurrent benchmarks
- ✅ No WebSocket complexity (uses HTTP SSE)

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         User Browser                             │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Live Component (BenchmarkProgress)                          │ │
│  │   │                                                          │ │
│  │   ├─ Stimulus Controller (mercure-progress)                 │ │
│  │   │   └─ EventSource → Mercure Hub (SSE)                    │ │
│  │   └─ Updates UI automatically                               │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │ SSE (Server-Sent Events)
                              │
┌─────────────────────────────────────────────────────────────────┐
│                      Mercure Hub (Docker)                        │
│  - Receives updates from backend                                 │
│  - Broadcasts to all subscribers                                 │
│  - Runs on http://localhost:3000                                 │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │ HTTP POST (publish updates)
                              │
┌─────────────────────────────────────────────────────────────────┐
│                      Symfony Backend                             │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ AsyncBenchmarkRunner                                        │ │
│  │   ├─ Dispatches: BenchmarkStarted                           │ │
│  │   ├─ Dispatches: BenchmarkProgress (every iteration)        │ │
│  │   └─ Dispatches: BenchmarkCompleted                         │ │
│  └────────────┬───────────────────────────────────────────────┘ │
│               │                                                   │
│               ▼                                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ BenchmarkProgressSubscriber (Event Listener)                │ │
│  │   └─ Publishes to Mercure Hub                               │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

## Components

### Backend Components

#### 1. Domain Events

**Location**: `src/Domain/Benchmark/Event/`

Three events track benchmark lifecycle:

**BenchmarkStarted** (`BenchmarkStarted.php`)
```php
new BenchmarkStarted(
    benchmarkId: 'App\Benchmark\LoopBenchmark',
    benchmarkName: 'Loop Performance',
    phpVersion: 'php84',
    totalIterations: 100
);
```

**BenchmarkProgress** (`BenchmarkProgress.php`)
```php
new BenchmarkProgress(
    benchmarkId: 'App\Benchmark\LoopBenchmark',
    benchmarkName: 'Loop Performance',
    phpVersion: 'php84',
    currentIteration: 50,
    totalIterations: 100
);
```

**BenchmarkCompleted** (`BenchmarkCompleted.php`)
```php
new BenchmarkCompleted(
    benchmarkId: 'App\Benchmark\LoopBenchmark',
    benchmarkName: 'Loop Performance',
    phpVersion: 'php84',
    totalIterations: 100
);
```

**Note**: Statistics (average, p90, p95, p99) are calculated by the `StatisticsCalculator` service and stored in the database. The completion event only signals that all iterations finished. Clients should query the API/database for detailed statistics.

#### 2. Event Subscriber

**Location**: `src/Infrastructure/Mercure/EventSubscriber/BenchmarkProgressSubscriber.php`

Listens to Domain events and publishes to Mercure:

```php
final readonly class BenchmarkProgressSubscriber implements EventSubscriberInterface
{
    public function __construct(private HubInterface $hub) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BenchmarkStarted::class => 'onBenchmarkStarted',
            BenchmarkProgress::class => 'onBenchmarkProgress',
            BenchmarkCompleted::class => 'onBenchmarkCompleted',
        ];
    }

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

#### 3. Use Case Integration

**Location**: `src/Application/UseCase/AsyncBenchmarkRunner.php:35`

The `AsyncBenchmarkRunner` dispatches events at key moments:

```php
public function run(BenchmarkConfiguration $config): void
{
    // 1. Dispatch start event
    $this->eventDispatcher->dispatch(new BenchmarkStarted(...));

    // 2. Run benchmark iterations
    for ($i = 0; $i < $config->iterations; ++$i) {
        $pool->add(...)->then(function ($result) {
            // 3. Dispatch progress after each iteration
            $this->eventDispatcher->dispatch(new BenchmarkProgress(...));
        });
    }

    $pool->wait();

    // 4. Dispatch completion event
    $this->eventDispatcher->dispatch(new BenchmarkCompleted(...));
}
```

### Frontend Components

#### 1. Live Component

**Location**: `src/Infrastructure/Web/Component/BenchmarkProgressComponent.php`

Symfony UX Live Component that renders benchmark progress:

```php
#[AsLiveComponent('BenchmarkProgress')]
final class BenchmarkProgressComponent
{
    #[LiveProp(writable: true)]
    public string $status = 'idle';

    #[LiveProp(writable: true)]
    public int $currentIteration = 0;

    public function getProgress(): int
    {
        return ($this->currentIteration / $this->totalIterations) * 100;
    }
}
```

**Template**: `templates/components/BenchmarkProgress.html.twig`

Displays:
- Progress bar with percentage
- Current iteration / Total iterations
- Benchmark results (average, p90, p95, p99)
- Status badges (idle, running, completed)

#### 2. Stimulus Controller

**Location**: `assets/controllers/mercure-progress_controller.js`

JavaScript controller that:
1. Connects to Mercure EventSource
2. Listens for SSE updates
3. Updates Live Component props
4. Provides immediate DOM updates

```javascript
export default class extends Controller {
    static values = {
        url: String,      // Mercure hub URL
        topic: String     // Topic to subscribe to
    };

    connect() {
        const url = new URL(this.urlValue);
        url.searchParams.append('topic', this.topicValue);

        this.eventSource = new EventSource(url);
        this.eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleBenchmarkUpdate(data);
        };
    }

    handleBenchmarkUpdate(data) {
        switch (data.type) {
            case 'benchmark.started':
                // Update UI to show "running" state
                break;
            case 'benchmark.progress':
                // Update progress bar
                break;
            case 'benchmark.completed':
                // Show final results
                break;
        }
    }
}
```

## Event Flow

### Complete Execution Flow

```
1. User runs benchmark:
   make run test=Loop iterations=100 version=php84

2. BenchmarkCommand (CLI)
   └─> ExecuteBenchmarkUseCase
       └─> BenchmarkOrchestrator
           └─> AsyncBenchmarkRunner.run()

3. AsyncBenchmarkRunner dispatches events:
   ┌─────────────────────────────────────────┐
   │ BenchmarkStarted                        │
   │ - benchmarkId: "Loop"                   │
   │ - phpVersion: "php84"                   │
   │ - totalIterations: 100                  │
   └─────────────────────────────────────────┘
                │
                ▼
   ┌─────────────────────────────────────────┐
   │ BenchmarkProgressSubscriber              │
   │ - Receives event                         │
   │ - Publishes to Mercure:                  │
   │   POST http://mercure/.well-known/mercure│
   │   topic: benchmark/progress              │
   │   data: {type: "benchmark.started", ...} │
   └─────────────────────────────────────────┘
                │
                ▼
   ┌─────────────────────────────────────────┐
   │ Mercure Hub                              │
   │ - Receives update                        │
   │ - Broadcasts to all subscribers          │
   └─────────────────────────────────────────┘
                │
                ▼
   ┌─────────────────────────────────────────┐
   │ Browser (EventSource)                    │
   │ - Receives SSE message                   │
   │ - Stimulus controller updates DOM        │
   │ - Progress bar shows 0%                  │
   └─────────────────────────────────────────┘

4. For each iteration (1 to 100):
   - Execute benchmark
   - Persist result
   - Dispatch BenchmarkProgress
   - Mercure broadcasts → Browser updates

5. After all iterations:
   - Dispatch BenchmarkCompleted
   - Mercure broadcasts completion signal
   - Browser displays "Completed" status
   - Detailed statistics are available in the dashboard (from database)
```

## Configuration

### Environment Variables

**File**: `.env`

```env
###> symfony/mercure-bundle ###
# Internal URL (backend → Mercure)
MERCURE_URL=http://mercure/.well-known/mercure

# Public URL (browser → Mercure)
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure

# JWT secret for authentication
MERCURE_JWT_SECRET="!ChangeThisMercureHubJWTSecretKey!"
###< symfony/mercure-bundle ###
```

**Important**:
- `MERCURE_URL`: Used by Symfony to publish updates (Docker internal network)
- `MERCURE_PUBLIC_URL`: Used by browser to subscribe (accessible from host)
- `MERCURE_JWT_SECRET`: Must match Docker environment variable

### Mercure Configuration

**File**: `config/packages/mercure.yaml`

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

### Docker Configuration

**File**: `docker-compose.yml:149`

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

**Key settings**:
- `SERVER_NAME: ':80'`: Disable HTTPS for development
- `anonymous`: Allow unauthenticated subscriptions
- `cors_origins`: Allow requests from Symfony app
- Port `3000`: Exposed for browser connections

## Usage

### Displaying Real-Time Progress

**In Twig template**:

```twig
{# Display real-time benchmark progress #}
<div class="benchmarks-container">
    {{ component('BenchmarkProgress') }}
</div>
```

The component will:
1. Connect to Mercure on page load
2. Subscribe to `benchmark/progress` topic
3. Automatically update when benchmarks run
4. Display progress bars and results

### Running Benchmarks

**CLI**:

```bash
# Run benchmark (will publish real-time updates)
make run test=Loop iterations=100 version=php84
```

**Expected flow**:
1. Browser page shows "Waiting to start..."
2. Benchmark starts → Status changes to "Running"
3. Progress bar updates every iteration
4. Completion → Shows final metrics (average, p90, p95, p99)

### Topics

**Available topics**:

| Topic | Description | Published By |
|-------|-------------|--------------|
| `benchmark/progress` | All progress updates (start, progress, complete) | `BenchmarkProgressSubscriber` |
| `benchmark/results` | Final results only | `BenchmarkProgressSubscriber` |

### Manual Testing

**Test Mercure connection**:

```bash
# Subscribe to updates (in terminal)
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress"

# Run benchmark (in another terminal)
make run test=Loop iterations=5

# You should see SSE events in first terminal
```

## Troubleshooting

### Mercure not receiving updates

**Symptom**: Benchmarks run but browser shows no updates

**Checks**:
1. Verify Mercure is running:
   ```bash
   docker-compose ps mercure
   # Should show "Up"
   ```

2. Check Mercure logs:
   ```bash
   docker-compose logs -f mercure
   ```

3. Test Mercure hub health:
   ```bash
   curl http://localhost:3000/.well-known/mercure
   # Should return 200 OK (empty body is normal)
   ```

4. Verify environment variables match:
   ```bash
   # In docker-compose.yml
   MERCURE_PUBLISHER_JWT_KEY: '!ChangeThisMercureHubJWTSecretKey!'

   # In .env
   MERCURE_JWT_SECRET="!ChangeThisMercureHubJWTSecretKey!"

   # Must be identical!
   ```

### CORS errors in browser console

**Symptom**: `Access-Control-Allow-Origin` errors

**Solution**: Update `docker-compose.yml:157`:

```yaml
MERCURE_EXTRA_DIRECTIVES: |
  cors_origins http://localhost:8000 http://127.0.0.1:8000
  anonymous
```

Add any additional domains your app uses.

### EventSource connection fails

**Symptom**: `ERR_CONNECTION_REFUSED` in browser

**Cause**: Mercure public URL incorrect

**Solution**: Verify `.env`:
```env
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
#                     ^^^^^^^^^^^^ Must be accessible from browser
```

### Progress bar not updating

**Symptom**: Events received but UI doesn't change

**Debug**:

1. Check browser console for errors
2. Verify Stimulus controller is loaded:
   ```javascript
   // In browser console
   document.querySelector('[data-controller="mercure-progress"]')
   ```

3. Check event data structure:
   ```javascript
   // In mercure-progress_controller.js
   handleBenchmarkUpdate(data) {
       console.log('Received:', data);
       // Verify data.type, data.currentIteration, etc.
   }
   ```

### Events dispatched but not reaching Mercure

**Symptom**: Backend logs show events dispatched, but Mercure receives nothing

**Checks**:

1. Verify `BenchmarkProgressSubscriber` is registered:
   ```bash
   docker-compose exec main php bin/console debug:event-dispatcher
   # Should show BenchmarkProgressSubscriber
   ```

2. Check subscriber is injecting HubInterface:
   ```bash
   docker-compose exec main php bin/console debug:autowiring HubInterface
   ```

3. Test publishing manually:
   ```php
   // In a test controller
   use Symfony\Component\Mercure\HubInterface;
   use Symfony\Component\Mercure\Update;

   public function test(HubInterface $hub): Response
   {
       $update = new Update('test/topic', json_encode(['message' => 'Hello']));
       $hub->publish($update);
       return new Response('Published');
   }
   ```

### Benchmarks running in background not publishing

**Symptom**: Benchmarks run asynchronously but events not dispatched

**Cause**: Async processes may not share same event dispatcher

**Solution**: Ensure `AsyncBenchmarkRunner` receives injected `EventDispatcherInterface`:

```php
// src/Application/UseCase/AsyncBenchmarkRunner.php:23
public function __construct(
    private EventDispatcherInterface $eventDispatcher, // ← Must be injected
) {}
```

## Performance Considerations

### Event Frequency

**Current behavior**: `BenchmarkProgress` dispatched after **every iteration**

For 1000 iterations:
- 1000 SSE messages sent
- May overwhelm browser/network

**Optimization**: Throttle progress events

```php
// AsyncBenchmarkRunner.php
if ($completedIterations % 10 === 0) { // Every 10 iterations
    $this->eventDispatcher->dispatch(new BenchmarkProgress(...));
}
```

### Mercure Connection Limit

Mercure has a default connection limit. For production:

```yaml
# docker-compose.yml (production)
MERCURE_SUBSCRIBER_HEARTBEAT_INTERVAL: '15s'
MERCURE_TRANSPORT_URL: 'bolt://mercure.db'
```

### Browser Memory

Long-running EventSource connections accumulate messages. Consider:

```javascript
// Auto-close after benchmark completes
handleBenchmarkUpdate(data) {
    if (data.type === 'benchmark.completed') {
        setTimeout(() => {
            this.eventSource.close();
        }, 5000); // Close after 5 seconds
    }
}
```

## Security

### Production Configuration

**DO NOT use in production**:
```yaml
MERCURE_EXTRA_DIRECTIVES: |
  anonymous  # ← Allows anyone to subscribe
```

**Production setup**:

1. Remove `anonymous` directive
2. Generate subscriber JWT tokens
3. Pass token to frontend:

```php
// In controller
use Symfony\Component\Mercure\Authorization;

public function dashboard(Authorization $authorization): Response
{
    $authorization->setCookie(
        $request,
        ['benchmark/progress'] // Topics user can access
    );
}
```

### JWT Secret Rotation

Change `MERCURE_JWT_SECRET` periodically:

```bash
# Generate new secret
openssl rand -base64 32

# Update .env and docker-compose.yml
# Restart containers
docker-compose restart mercure main
```

---

## References

- [Mercure Protocol Specification](https://mercure.rocks/spec)
- [Symfony Mercure Bundle](https://symfony.com/bundles/MercureBundle/current/index.html)
- [Symfony UX Live Components](https://symfony.com/bundles/ux-live-component/current/index.html)
- [Server-Sent Events (SSE) API](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events)
- [Stimulus Controllers](https://stimulus.hotwired.dev/)

---

**Last updated**: 2025-10-22
**Maintained by**: Project contributors
