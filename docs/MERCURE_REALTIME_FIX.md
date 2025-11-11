# 🔧 Fix: Real-Time Mercure Updates

## Problem

The real-time updates with Mercure were not working even though:
- Benchmarks executed successfully
- Messages were processed by workers
- No errors in the logs

## Investigation

### 1. Initial Issue (Already Fixed)
**Property name typo in ExecuteBenchmarkHandler:**
```php
// ❌ INCORRECT
'memory_usage_bytes' => $result->memoryUsageBytes,

// ✅ CORRECT
'memory_usage_bytes' => $result->memoryUsedBytes,
```

### 2. Second Issue (This Fix)
**Event Dispatching Context Problem:**

The `BenchmarkStarted` and `BenchmarkCompleted` events were dispatched in the CLI process (command), but the `BenchmarkProgressSubscriber` (which publishes to Mercure) was only active in the worker processes.

**Event Flow:**
```
CLI Process (benchmark:run command)
├── MessengerBenchmarkRunner
│   ├── dispatch(BenchmarkStarted) ❌ No Mercure subscriber here
│   ├── dispatch messages to queue
│   └── dispatch(BenchmarkCompleted) ❌ No Mercure subscriber here
│
Worker Process
├── ExecuteBenchmarkHandler
│   └── dispatch(BenchmarkProgress) ✅ Mercure subscriber active
│
Result: Only Progress events were published to Mercure
```

## Solution

### 1. Add BenchmarkProgress Component to Dashboard

```diff
+        {# Real-time Progress Display #}
+        <twig:BenchmarkProgress />
+        
         {# Benchmark Cards Container #}
         <div class="dashboard__benchmarks">
```

### 2. Move Event Dispatching to Workers

**ExecuteBenchmarkHandler.php:**
```php
// Dispatch start event for first iteration
if ($message->iterationNumber === 1) {
    $this->eventDispatcher->dispatch(
        new BenchmarkStarted(...)
    );
}

// ... execute benchmark ...

// Dispatch progress event (always)
$this->eventDispatcher->dispatch(
    new BenchmarkProgress(...)
);

// Dispatch completed event for last iteration
if ($message->iterationNumber === $message->iterations) {
    $this->eventDispatcher->dispatch(
        new BenchmarkCompleted(...)
    );
}
```

**MessengerBenchmarkRunner.php:**
```php
// Remove event dispatching from CLI context
// Events are now dispatched by workers
```

## Result

All events are now dispatched within worker processes where the Mercure subscriber is active:

```
Worker Process
├── ExecuteBenchmarkHandler
│   ├── dispatch(BenchmarkStarted) ✅ First iteration
│   ├── dispatch(BenchmarkProgress) ✅ All iterations
│   └── dispatch(BenchmarkCompleted) ✅ Last iteration
│
├── BenchmarkProgressSubscriber (active)
│   ├── onBenchmarkStarted() → Publish to Mercure
│   ├── onBenchmarkProgress() → Publish to Mercure
│   └── onBenchmarkCompleted() → Publish to Mercure
```

## Verification

Run a benchmark and check the logs:

```bash
make run test=abs-with-abs iterations=3
```

You should see:
```
📢 BenchmarkProgressSubscriber::onBenchmarkStarted called
📢 BenchmarkProgressSubscriber::onBenchmarkProgress called
📢 BenchmarkProgressSubscriber::onBenchmarkCompleted called
```

## Real-Time Updates Now Working

1. **BenchmarkStarted** - Shows benchmark name and PHP version
2. **BenchmarkProgress** - Updates progress bar in real-time
3. **BenchmarkCompleted** - Marks completion and refreshes dashboard

## Topics

- `benchmark/progress` - Used by BenchmarkProgress component
- `benchmark/results` - Used by dashboard for completed benchmarks