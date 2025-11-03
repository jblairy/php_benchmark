# ADR-003: Use Mercure for Real-Time Updates

**Status**: Accepted  
**Date**: 2024-08-28  
**Deciders**: Development Team

## Context

Benchmark execution can take several seconds to minutes (1000+ iterations per method). Users need real-time feedback showing:

- Progress percentage (0-100%)
- Current iteration count
- Estimated time remaining
- Live status updates (running, completed, failed)

Without real-time updates, users would see a blank screen or spinner, leading to poor user experience and uncertainty about execution status.

### Requirements
- **Server-to-client push**: Server must push updates without client polling
- **Low latency**: Updates should appear within milliseconds
- **Symfony integration**: Must work seamlessly with Symfony ecosystem
- **Scalability**: Support multiple concurrent benchmark executions

### Options Considered

1. **HTTP Polling**: Client requests updates every N seconds
2. **WebSockets**: Full-duplex persistent connection (Socket.io, Ratchet)
3. **Server-Sent Events (SSE)**: Unidirectional server-to-client stream
4. **Mercure**: SSE-based protocol with Symfony integration

## Decision

We use **Mercure** (SSE-based protocol) for real-time updates because:

1. **Native Symfony integration**: Official `symfony/mercure-bundle` with minimal configuration
2. **Simpler than WebSockets**: Unidirectional communication (server → client) sufficient for our use case
3. **HTTP/2 compatible**: Works over standard HTTP, no protocol upgrade needed
4. **Built-in reconnection**: Automatic reconnection and event replay on disconnect
5. **Topic-based subscriptions**: Clients subscribe to specific benchmark updates via URLs
6. **Production-ready**: Standalone Mercure Hub (Go binary) handles thousands of connections

### Architecture

```
PHP Application (Symfony)
    ↓ publishes updates
Mercure Hub (:3000)
    ↓ broadcasts via SSE
Browser (EventSource)
    ↓ updates UI (Stimulus)
```

### Implementation

**Server-side** (`BenchmarkProgressPublisher`):
```php
$this->hub->publish(new Update(
    topics: ["http://app/benchmark/{$id}"],
    data: json_encode(['progress' => 45, 'iteration' => 450])
));
```

**Client-side** (`mercure-progress_controller.js`):
```javascript
const url = new URL(mercureUrl);
url.searchParams.append('topic', `http://app/benchmark/${id}`);
const eventSource = new EventSource(url);
eventSource.onmessage = (e) => updateProgress(JSON.parse(e.data));
```

## Consequences

### Positive
- **Real-time UX**: Users see live progress without refresh
- **Efficient**: No polling overhead, server pushes only when data changes
- **Resilient**: Auto-reconnection and event replay prevent data loss
- **Developer-friendly**: Simple publish/subscribe API
- **Scalable**: Mercure Hub handles connections independently from PHP processes

### Negative
- **Additional service**: Requires running Mercure Hub (Docker container in our case)
- **Network dependency**: Real-time updates fail if Mercure Hub is down
- **Browser support**: Older browsers may not support EventSource (IE11)
- **Debugging**: SSE connections harder to inspect than HTTP requests

### Trade-offs Accepted
- We accept operational overhead of running Mercure Hub for improved UX
- We accept SSE limitations (unidirectional) since we don't need client-to-server streaming
- We provide fallback: polling mechanism if EventSource unavailable (not implemented yet)

## Alternatives Not Chosen

### HTTP Polling
```javascript
setInterval(() => fetch('/benchmark/status'), 2000);
```
**Rejected**: Inefficient (wasted requests when no updates), higher latency, server load

### WebSockets (Socket.io/Ratchet)
**Rejected**: Overkill for unidirectional updates, more complex setup, requires protocol upgrade

### Server-Sent Events (Raw SSE)
**Rejected**: Would need to implement reconnection, event replay, and topic routing ourselves

## Configuration

```yaml
# config/packages/mercure.yaml
mercure:
    hubs:
        default:
            url: '%env(MERCURE_URL)%'
            public_url: '%env(MERCURE_PUBLIC_URL)%'
            jwt_secret: '%env(MERCURE_JWT_SECRET)%'
```

```yaml
# docker-compose.yml
mercure:
    image: dunglas/mercure
    environment:
        MERCURE_PUBLISHER_JWT_KEY: 'your-secret-key'
        MERCURE_SUBSCRIBER_JWT_KEY: 'your-secret-key'
```

## References
- [Mercure Protocol Specification](https://mercure.rocks/)
- [Symfony Mercure Bundle](https://symfony.com/doc/current/mercure.html)
- Project implementation: `docs/infrastructure/mercure-index.md`
- Testing scripts: `scripts/mercure-test.sh`, `scripts/mercure-verify.sh`
