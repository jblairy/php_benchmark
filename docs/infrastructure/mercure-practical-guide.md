# Mercure Practical Guide - Debugging & Usage

This practical guide explains how to work with Mercure in the PHP Benchmark project, including debugging techniques, verification steps, and common usage scenarios.

## Table of Contents

1. [What We Built](#what-we-built)
2. [How to Debug Mercure](#how-to-debug-mercure)
3. [Verification Checklist](#verification-checklist)
4. [Common Use Cases](#common-use-cases)
5. [Useful Commands](#useful-commands)
6. [Troubleshooting Workflows](#troubleshooting-workflows)

---

## What We Built

### Implementation Summary

**Objective**: Display real-time benchmark progress in the browser without page refresh

**Technology Stack**:
- **Mercure Hub**: Server-Sent Events (SSE) broadcasting server
- **Symfony Events**: Domain events dispatched during benchmark execution
- **Event Subscriber**: Publishes events to Mercure
- **Live Components**: Reactive UI components
- **Stimulus**: JavaScript for EventSource connection

### Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│ Browser (http://localhost:8000)                              │
│                                                               │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Live Component: BenchmarkProgress                       │ │
│  │   - Displays progress bar                               │ │
│  │   - Shows current iteration                             │ │
│  │   - Updates automatically                               │ │
│  └────────────────────────────────────────────────────────┘ │
│                     ▲                                         │
│                     │ SSE (Server-Sent Events)               │
│                     │                                         │
└─────────────────────┼─────────────────────────────────────────┘
                      │
┌─────────────────────┼─────────────────────────────────────────┐
│                     │                                          │
│  ┌──────────────────┴───────────────────────────────────┐    │
│  │ Mercure Hub (http://localhost:3000)                  │    │
│  │   - Receives updates from Symfony                     │    │
│  │   - Broadcasts to all connected browsers              │    │
│  │   - Topic: "benchmark/progress"                       │    │
│  └──────────────────▲───────────────────────────────────┘    │
│                     │                                          │
│                     │ HTTP POST                                │
│                     │                                          │
│  ┌──────────────────┴───────────────────────────────────┐    │
│  │ Symfony Application                                   │    │
│  │                                                        │    │
│  │  1. AsyncBenchmarkRunner                              │    │
│  │     - Dispatches: BenchmarkStarted                    │    │
│  │     - Dispatches: BenchmarkProgress (each iteration)  │    │
│  │     - Dispatches: BenchmarkCompleted                  │    │
│  │                                                        │    │
│  │  2. BenchmarkProgressSubscriber                       │    │
│  │     - Listens to Domain events                        │    │
│  │     - Publishes to Mercure Hub                        │    │
│  │     - Format: JSON over HTTP                          │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                               │
└───────────────────────────────────────────────────────────────┘
```

### What Happens Step-by-Step

**1. User opens dashboard in browser**
```
Browser → http://localhost:8000/dashboard
BenchmarkProgress component loads
Stimulus controller connects to Mercure
EventSource opens: http://localhost:3000/.well-known/mercure?topic=benchmark/progress
```

**2. User runs benchmark from CLI**
```bash
make run test=Loop iterations=10 version=php84
```

**3. AsyncBenchmarkRunner dispatches events**
```
Iteration 0: BenchmarkStarted event
Iteration 1: BenchmarkProgress event (10%)
Iteration 2: BenchmarkProgress event (20%)
...
Iteration 10: BenchmarkProgress event (100%)
After pool.wait(): BenchmarkCompleted event
```

**4. BenchmarkProgressSubscriber publishes to Mercure**
```php
// For each event
$update = new Update(
    'benchmark/progress',
    json_encode([
        'type' => 'benchmark.progress',
        'benchmarkId' => 'Loop',
        'currentIteration' => 5,
        'totalIterations' => 10,
        'progress' => 50
    ])
);
$hub->publish($update);
```

**5. Mercure broadcasts to all subscribers**
```
POST http://mercure/.well-known/mercure
Topic: benchmark/progress
Data: JSON event

Mercure → All connected EventSource clients
```

**6. Browser receives SSE and updates UI**
```javascript
eventSource.onmessage = (event) => {
    const data = JSON.parse(event.data);
    // Update progress bar: 10% → 20% → 30% → ... → 100%
};
```

---

## How to Debug Mercure

### 1. Check Mercure Service is Running

**Command**:
```bash
docker-compose ps mercure
```

**Expected output**:
```
NAME                      IMAGE             COMMAND                  SERVICE   CREATED          STATUS          PORTS
php_benchmark-mercure-1   dunglas/mercure   "/usr/bin/caddy run …"   mercure   10 minutes ago   Up 10 minutes   443/tcp, 2019/tcp, 0.0.0.0:3000->80/tcp
```

**What to check**:
- ✅ **STATUS**: Should be "Up" (not "Exited" or "Restarting")
- ✅ **PORTS**: Should show `0.0.0.0:3000->80/tcp`

**If not running**:
```bash
# Start Mercure
docker-compose up -d mercure

# Check logs
docker-compose logs -f mercure
```

---

### 2. Verify Mercure is Accessible

**Command**:
```bash
curl -i http://localhost:3000/.well-known/mercure
```

**Expected output**:
```http
HTTP/1.1 400 Bad Request
Server: Caddy
Content-Type: text/plain; charset=utf-8

Missing "topic" parameter
```

**Interpretation**:
- ✅ **400 Bad Request** is NORMAL (we didn't provide a topic)
- ✅ **Server: Caddy** confirms Mercure is responding
- ❌ **Connection refused** means Mercure is not accessible

---

### 3. Subscribe to Events (Manual Test)

**Command** (keeps running, press Ctrl+C to stop):
```bash
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress"
```

**Expected behavior**:
- Command hangs (this is normal - it's waiting for events)
- No output initially
- When you run a benchmark, you'll see SSE messages

**Sample output when benchmark runs**:
```
id: urn:uuid:bc8bc7a8-e0c5-40af-8547-dd21fc4a7306
data: {"type":"benchmark.started","benchmarkId":"Jblairy\\PhpBenchmark\\Domain\\Benchmark\\Test\\Loop","benchmarkName":"Loop","phpVersion":"php84","totalIterations":5,"timestamp":1761113607}

id: urn:uuid:11031f73-897a-4d97-bf13-debe15b5ef20
data: {"type":"benchmark.progress","benchmarkId":"Jblairy\\PhpBenchmark\\Domain\\Benchmark\\Test\\Loop","benchmarkName":"Loop","phpVersion":"php84","currentIteration":1,"totalIterations":5,"progress":20,"timestamp":1761113608}
```

**SSE format explained**:
- `id:` - Unique event identifier (UUID)
- `data:` - JSON payload with event information
- Blank line separates events

---

### 4. Check Event Publishing from Symfony

**Command** (check if subscriber is registered):
```bash
docker-compose exec main php bin/console debug:event-dispatcher
```

**Look for**:
```
Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkStarted
  - Jblairy\PhpBenchmark\Infrastructure\Mercure\EventSubscriber\BenchmarkProgressSubscriber::onBenchmarkStarted

Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkProgress
  - Jblairy\PhpBenchmark\Infrastructure\Mercure\EventSubscriber\BenchmarkProgressSubscriber::onBenchmarkProgress

Jblairy\PhpBenchmark\Domain\Benchmark\Event\BenchmarkCompleted
  - Jblairy\PhpBenchmark\Infrastructure\Mercure\EventSubscriber\BenchmarkProgressSubscriber::onBenchmarkCompleted
```

**Interpretation**:
- ✅ All 3 events should have `BenchmarkProgressSubscriber` handlers
- ❌ If missing, subscriber is not registered (check autowiring)

---

### 5. Test End-to-End Publishing

**Terminal 1** (subscribe to Mercure):
```bash
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress"
```

**Terminal 2** (run a short benchmark):
```bash
docker-compose exec main php bin/console benchmark:run --test=Loop --php-version=php84 --iterations=3
```

**Expected flow**:
1. Terminal 2 shows: "Running Loop on php84 (3 iterations)"
2. Terminal 1 receives 5 SSE events:
   - 1 `benchmark.started`
   - 3 `benchmark.progress` (33%, 66%, 100%)
   - 1 `benchmark.completed`
3. Terminal 2 shows: "[OK] Benchmark(s) completed successfully!"

**If no events in Terminal 1**:
- Check Mercure logs: `docker-compose logs -f mercure`
- Check Symfony logs: `docker-compose logs -f main`
- Verify environment variables (see section below)

---

### 6. Check Environment Variables

**Command**:
```bash
docker-compose exec main env | grep MERCURE
```

**Expected output**:
```
MERCURE_URL=http://mercure/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_SECRET=!ChangeThisMercureHubJWTSecretKey!
```

**Verify in docker-compose.yml**:
```bash
grep -A 5 "MERCURE_PUBLISHER_JWT_KEY" docker-compose.yml
```

**Expected**:
```yaml
MERCURE_PUBLISHER_JWT_KEY: '!ChangeThisMercureHubJWTSecretKey!'
MERCURE_SUBSCRIBER_JWT_KEY: '!ChangeThisMercureHubJWTSecretKey!'
```

**Critical**: `MERCURE_JWT_SECRET` must match `MERCURE_PUBLISHER_JWT_KEY`

---

### 7. Check Mercure Logs for Errors

**Command**:
```bash
docker-compose logs -f mercure
```

**Look for**:
- ✅ `"server running"` - Mercure started successfully
- ✅ `"handled request"` - Mercure is processing requests
- ❌ `"authentication failed"` - JWT secret mismatch
- ❌ `"cors error"` - CORS configuration issue

**Example healthy log**:
```json
{"level":"info","msg":"server running","name":"srv0","protocols":["h1"]}
{"level":"info","msg":"handled request","method":"GET","uri":"/.well-known/mercure","status":200}
```

---

### 8. Test with Browser DevTools

**Steps**:
1. Open browser to `http://localhost:8000`
2. Open DevTools (F12)
3. Go to **Network** tab
4. Filter: `mercure`
5. Run a benchmark

**What to look for**:

**Request**:
```
GET http://localhost:3000/.well-known/mercure?topic=benchmark%2Fprogress
Status: 200 OK
Type: eventsource
```

**Response headers**:
```
Content-Type: text/event-stream
Cache-Control: no-cache
Connection: keep-alive
```

**EventStream tab** (in Chrome):
```
id: urn:uuid:...
data: {"type":"benchmark.started",...}

id: urn:uuid:...
data: {"type":"benchmark.progress",...}
```

**Console** (check for errors):
- ❌ `CORS error` - Check CORS configuration
- ❌ `EventSource failed` - Check Mercure URL
- ✅ No errors - Working correctly

---

### 9. Debug Symfony Event Publishing

**Add temporary logging in BenchmarkProgressSubscriber**:

```php
// src/Infrastructure/Mercure/EventSubscriber/BenchmarkProgressSubscriber.php

use Psr\Log\LoggerInterface;

public function __construct(
    private HubInterface $hub,
    private LoggerInterface $logger, // Add this
) {}

public function onBenchmarkProgress(BenchmarkProgress $event): void
{
    $this->logger->info('Publishing benchmark progress', [
        'benchmarkId' => $event->benchmarkId,
        'currentIteration' => $event->currentIteration,
        'totalIterations' => $event->totalIterations,
    ]);

    $this->publishUpdate('benchmark/progress', $event->toArray());
}
```

**Check logs**:
```bash
docker-compose exec main tail -f var/log/dev.log
```

**Expected**:
```
[info] Publishing benchmark progress {"benchmarkId":"Loop","currentIteration":1,"totalIterations":5}
[info] Publishing benchmark progress {"benchmarkId":"Loop","currentIteration":2,"totalIterations":5}
```

---

## Verification Checklist

Use this checklist to verify Mercure is working correctly:

### Infrastructure
- [ ] Mercure container running: `docker-compose ps mercure`
- [ ] Port 3000 exposed: `curl http://localhost:3000/.well-known/mercure`
- [ ] No errors in Mercure logs: `docker-compose logs mercure`

### Configuration
- [ ] Environment variables set: `docker-compose exec main env | grep MERCURE`
- [ ] JWT secrets match (`.env` vs `docker-compose.yml`)
- [ ] CORS configured: `grep cors_origins docker-compose.yml`

### Symfony Integration
- [ ] Event subscriber registered: `php bin/console debug:event-dispatcher`
- [ ] HubInterface autowired: `php bin/console debug:autowiring HubInterface`
- [ ] Mercure bundle configured: `cat config/packages/mercure.yaml`

### Publishing
- [ ] Events dispatched during benchmark: Check logs
- [ ] Subscriber publishes to Mercure: Add logging
- [ ] Mercure receives updates: Monitor logs

### Browser
- [ ] EventSource connects: Check Network tab
- [ ] SSE messages received: Check EventStream tab
- [ ] UI updates automatically: Run benchmark and watch
- [ ] No CORS errors: Check Console tab

---

## Common Use Cases

### Use Case 1: Monitor Single Benchmark in Real-Time

**Scenario**: You're optimizing a specific benchmark and want to see progress live

**Steps**:

1. **Open dashboard in browser**:
   ```
   http://localhost:8000
   ```

2. **Add progress component** (if not already in template):
   ```twig
   {{ component('BenchmarkProgress') }}
   ```

3. **Run benchmark with many iterations**:
   ```bash
   make run test=Loop iterations=100 version=php84
   ```

4. **Watch the UI**:
   - Progress bar fills from 0% → 100%
   - Shows "X / 100" iterations
   - Completes with "Completed! 100 iterations finished."

**Expected time**: ~30 seconds for 100 iterations

---

### Use Case 2: Debug Benchmark Execution

**Scenario**: Benchmark is taking too long, you want to see if it's stuck

**Terminal 1** (watch events):
```bash
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress" | \
  jq -R 'select(startswith("data:")) | sub("^data: "; "") | fromjson'
```

**Terminal 2** (run benchmark):
```bash
make run test=YourSlowBenchmark iterations=50 version=php84
```

**Terminal 1 output** (formatted JSON):
```json
{"type":"benchmark.started","benchmarkName":"YourSlowBenchmark","totalIterations":50}
{"type":"benchmark.progress","currentIteration":1,"progress":2}
{"type":"benchmark.progress","currentIteration":2,"progress":4}
... (wait) ...
{"type":"benchmark.progress","currentIteration":3,"progress":6}
```

**Interpretation**:
- If events stop coming → Benchmark is stuck
- If events are slow but regular → Benchmark is just slow
- Check time between events to measure iteration speed

---

### Use Case 3: Compare Multiple PHP Versions

**Scenario**: Run same benchmark on all PHP versions and monitor progress

**Terminal 1** (subscribe):
```bash
# Pretty-print with jq for readability
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress" | \
  while IFS= read -r line; do
    if [[ $line == data:* ]]; then
      echo "$line" | sed 's/^data: //' | jq -c '{type,phpVersion,progress}'
    fi
  done
```

**Terminal 2** (run on all versions):
```bash
make run test=Loop iterations=10
```

**Terminal 1 output**:
```json
{"type":"benchmark.started","phpVersion":"php56","progress":null}
{"type":"benchmark.progress","phpVersion":"php56","progress":10}
{"type":"benchmark.progress","phpVersion":"php56","progress":20}
...
{"type":"benchmark.completed","phpVersion":"php56","progress":null}
{"type":"benchmark.started","phpVersion":"php70","progress":null}
{"type":"benchmark.progress","phpVersion":"php70","progress":10}
...
```

**See progression across all PHP versions in real-time**

---

### Use Case 4: Automated Testing with Event Capture

**Scenario**: CI/CD pipeline that validates benchmarks complete successfully

**Script** (`test-benchmark-events.sh`):
```bash
#!/bin/bash

# Start listening to Mercure in background
timeout 60 curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress" > /tmp/events.log 2>&1 &
CURL_PID=$!

# Give curl time to connect
sleep 2

# Run benchmark
docker-compose exec -T main php bin/console benchmark:run --test=Loop --php-version=php84 --iterations=5

# Wait for events to be captured
sleep 3

# Kill curl
kill $CURL_PID 2>/dev/null

# Parse events
STARTED=$(grep -c '"benchmark.started"' /tmp/events.log)
PROGRESS=$(grep -c '"benchmark.progress"' /tmp/events.log)
COMPLETED=$(grep -c '"benchmark.completed"' /tmp/events.log)

echo "Events received:"
echo "  Started: $STARTED"
echo "  Progress: $PROGRESS"
echo "  Completed: $COMPLETED"

# Validate
if [ "$STARTED" -eq 1 ] && [ "$PROGRESS" -eq 5 ] && [ "$COMPLETED" -eq 1 ]; then
  echo "✅ All events received correctly"
  exit 0
else
  echo "❌ Missing events"
  exit 1
fi
```

**Usage**:
```bash
chmod +x test-benchmark-events.sh
./test-benchmark-events.sh
```

---

### Use Case 5: Live Dashboard for Team

**Scenario**: Team dashboard showing benchmark progress on a TV screen

**Create dedicated route** (`src/Infrastructure/Web/Controller/LiveDashboardController.php`):
```php
<?php

namespace Jblairy\PhpBenchmark\Infrastructure\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class LiveDashboardController extends AbstractController
{
    #[Route('/live', name: 'live_dashboard')]
    public function index(): Response
    {
        return $this->render('live_dashboard/index.html.twig');
    }
}
```

**Template** (`templates/live_dashboard/index.html.twig`):
```twig
<!DOCTYPE html>
<html>
<head>
    <title>Live Benchmark Dashboard</title>
    <style>
        body {
            background: #1a1a1a;
            color: #fff;
            font-family: monospace;
            font-size: 20px;
        }
        .progress-container {
            padding: 2rem;
            margin: 2rem;
            background: #2a2a2a;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <h1>🚀 Live Benchmark Progress</h1>

    <div class="progress-container">
        {{ component('BenchmarkProgress') }}
    </div>

    {# Auto-refresh every hour to prevent stale connections #}
    <script>
        setTimeout(() => location.reload(), 3600000);
    </script>
</body>
</html>
```

**Access**: `http://localhost:8000/live`

**Display on TV**: Full-screen browser, shows all benchmarks running in real-time

---

## Useful Commands

### Mercure Management

**Start Mercure**:
```bash
docker-compose up -d mercure
```

**Stop Mercure**:
```bash
docker-compose stop mercure
```

**Restart Mercure** (after config changes):
```bash
docker-compose restart mercure
```

**View Mercure logs** (follow mode):
```bash
docker-compose logs -f mercure
```

**View last 50 lines**:
```bash
docker-compose logs --tail=50 mercure
```

**Check Mercure resource usage**:
```bash
docker stats php_benchmark-mercure-1
```

---

### Testing & Debugging

**Subscribe to events** (basic):
```bash
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress"
```

**Subscribe with JSON formatting** (requires `jq`):
```bash
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress" | \
  grep '^data:' | sed 's/^data: //' | jq .
```

**Count events received**:
```bash
timeout 30 curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress" | \
  grep -c '^data:'
```

**Extract only progress percentages**:
```bash
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress" | \
  grep '^data:' | sed 's/^data: //' | jq -r '.progress // empty'
```

**Monitor events with timestamps**:
```bash
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress" | \
  while IFS= read -r line; do
    if [[ $line == data:* ]]; then
      echo "[$(date '+%H:%M:%S')] $line"
    fi
  done
```

---

### Benchmark Execution

**Run single benchmark with progress**:
```bash
make run test=Loop iterations=10 version=php84
```

**Run all benchmarks** (watch progress for each):
```bash
make run iterations=5
```

**Run specific test on all PHP versions**:
```bash
# Terminal 1: Watch events
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress"

# Terminal 2: Run benchmark
docker-compose exec main php bin/console benchmark:run --test=Loop --iterations=5
```

---

### Environment & Configuration

**Check Mercure environment variables**:
```bash
docker-compose exec main env | grep MERCURE
```

**Validate Mercure config**:
```bash
docker-compose exec main php bin/console debug:config mercure
```

**Check event subscribers**:
```bash
docker-compose exec main php bin/console debug:event-dispatcher | grep Benchmark
```

**Verify HubInterface autowiring**:
```bash
docker-compose exec main php bin/console debug:autowiring HubInterface
```

---

### Performance Monitoring

**Monitor Mercure CPU/Memory**:
```bash
watch -n 1 'docker stats --no-stream php_benchmark-mercure-1'
```

**Count active SSE connections** (check Mercure logs):
```bash
docker-compose logs mercure | grep -c "subscribe"
```

**Measure event throughput**:
```bash
# Run benchmark
time make run test=Loop iterations=100 version=php84

# Count events published
docker-compose logs mercure | grep -c "POST /.well-known/mercure"
```

---

### Data Analysis

**Extract all events to file**:
```bash
timeout 60 curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress" > benchmark_events.txt
```

**Parse events to CSV**:
```bash
cat benchmark_events.txt | \
  grep '^data:' | \
  sed 's/^data: //' | \
  jq -r '[.type, .benchmarkName, .phpVersion, .currentIteration, .totalIterations, .progress] | @csv'
```

**Count events by type**:
```bash
cat benchmark_events.txt | \
  grep '^data:' | \
  sed 's/^data: //' | \
  jq -r '.type' | \
  sort | uniq -c
```

**Expected output**:
```
   1 benchmark.completed
   5 benchmark.progress
   1 benchmark.started
```

---

## Troubleshooting Workflows

### Problem: No events received in browser

**Diagnosis workflow**:

```bash
# Step 1: Check Mercure is running
docker-compose ps mercure
# Expected: Status "Up"

# Step 2: Check Mercure is accessible
curl -i http://localhost:3000/.well-known/mercure
# Expected: 400 Bad Request (missing topic)

# Step 3: Subscribe manually
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress" &
CURL_PID=$!

# Step 4: Run benchmark
make run test=Loop iterations=3 version=php84

# Step 5: Check if events appeared
# If yes: Problem is in browser/JavaScript
# If no: Problem is in Symfony publishing

# Step 6: Kill curl
kill $CURL_PID
```

**If events don't appear**:

```bash
# Check Symfony is dispatching events
docker-compose exec main php bin/console debug:event-dispatcher | grep BenchmarkProgress

# Check subscriber is registered
docker-compose logs main | grep "Publishing benchmark"

# Check Mercure logs for POST requests
docker-compose logs mercure | grep POST
```

---

### Problem: CORS errors in browser

**Diagnosis**:

```bash
# Check browser console
# Error: "Access-Control-Allow-Origin"

# Check Mercure CORS config
docker-compose exec mercure cat /etc/caddy/dev.Caddyfile | grep cors
```

**Solution**:

Update `docker-compose.yml:157`:
```yaml
MERCURE_EXTRA_DIRECTIVES: |
  cors_origins http://localhost:8000 http://127.0.0.1:8000
  anonymous
```

Then restart:
```bash
docker-compose restart mercure
```

**Verify**:
```bash
# Check response headers
curl -i "http://localhost:3000/.well-known/mercure?topic=test" \
  -H "Origin: http://localhost:8000"

# Should include:
# Access-Control-Allow-Origin: http://localhost:8000
```

---

### Problem: Events are delayed or batched

**Diagnosis**:

```bash
# Monitor events with timestamps
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress" | \
  while read -r line; do
    [[ $line == data:* ]] && echo "[$(date +%H:%M:%S.%N)] $line"
  done
```

**Expected**: Events appear immediately as benchmark runs

**If delayed**:
- Check network latency
- Check Mercure logs for slow POST requests
- Check Symfony is not buffering output

**Solution** (if buffering):
```php
// In BenchmarkProgressSubscriber
private function publishUpdate(string $topic, array $data): void
{
    $update = new Update($topic, json_encode($data));
    $this->hub->publish($update);

    // Force flush (if needed)
    if (function_exists('flush')) {
        flush();
    }
}
```

---

### Problem: Too many events (high CPU)

**Diagnosis**:

```bash
# Count events during benchmark
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress" | \
  timeout 30 grep -c '^data:'

# For 100 iterations:
# Expected: ~102 events (1 start + 100 progress + 1 complete)
```

**If too many events** (e.g., 1000+ for 100 iterations):

Check `AsyncBenchmarkRunner.php:54` - should dispatch only once per iteration, not multiple times

**Solution** (throttle events):
```php
// AsyncBenchmarkRunner.php
if ($completedIterations % 10 === 0 || $completedIterations === $totalIterations) {
    $this->eventDispatcher->dispatch(new BenchmarkProgress(...));
}
```

**Result**: 100 iterations → 11 events (10%, 20%, ..., 100%)

---

## Quick Reference Card

**Common Commands**:

| Task | Command |
|------|---------|
| Start Mercure | `docker-compose up -d mercure` |
| Check status | `docker-compose ps mercure` |
| View logs | `docker-compose logs -f mercure` |
| Test connection | `curl http://localhost:3000/.well-known/mercure` |
| Subscribe | `curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress"` |
| Run benchmark | `make run test=Loop iterations=5 version=php84` |
| Debug events | `docker-compose exec main php bin/console debug:event-dispatcher` |
| Check config | `docker-compose exec main php bin/console debug:config mercure` |

**Ports**:
- `3000` - Mercure Hub (SSE)
- `8000` - Symfony application
- `3306` - MariaDB

**Topics**:
- `benchmark/progress` - All progress events (start, progress, complete)
- `benchmark/results` - Final results only (not used currently)

**Environment Variables**:
- `MERCURE_URL` - Internal URL (backend → Mercure)
- `MERCURE_PUBLIC_URL` - Public URL (browser → Mercure)
- `MERCURE_JWT_SECRET` - Authentication secret

---

## Further Reading

- [Mercure Real-Time Guide](mercure-realtime.md) - Complete architecture documentation
- [Docker Infrastructure](docker.md) - Overall Docker setup
- [Mercure Protocol Spec](https://mercure.rocks/spec) - Official specification
- [Server-Sent Events API](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events) - Browser API

---

**Last updated**: 2025-10-22
**Maintained by**: Project contributors
