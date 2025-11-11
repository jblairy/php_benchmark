# FrankenPHP Worker Mode Configuration

## Overview

This document describes the FrankenPHP worker mode configuration for the PHP Benchmark project. Worker mode enables persistent PHP application execution, significantly improving performance by eliminating bootstrap overhead on each request.

## Configuration Files

### Development: `docker/frankenphp/Caddyfile.dev`

```caddyfile
{
    auto_https off
    order php_server before file_server
}

:80 {
    root * /app/public
    encode gzip zstd
    log {
        output stdout
        format console
        level DEBUG
    }
    php_server {
        worker index.php 2
    }
}
```

**Configuration Details:**
- **Workers**: 2 (reduced for development)
- **Worker Script**: `index.php` (relative to root `/app/public`)
- **Logging**: Console format with DEBUG level
- **Compression**: gzip and zstd enabled

### Production: `docker/frankenphp/Caddyfile`

```caddyfile
{
    auto_https off
    order php_server before file_server
}

:80 {
    root * /app/public
    encode gzip zstd
    log {
        output stdout
        format json
        level INFO
    }
    php_server {
        worker index.php 4
    }
}
```

**Configuration Details:**
- **Workers**: 4 (optimized for production)
- **Worker Script**: `index.php` (relative to root `/app/public`)
- **Logging**: JSON format with INFO level
- **Compression**: gzip and zstd enabled

## Worker Mode Syntax

### Correct Syntax (Short Form)

```caddyfile
php_server {
    worker <file> <num>
}
```

**Parameters:**
- `<file>`: Path to worker script (relative to root or absolute)
- `<num>`: Number of worker threads to start

**Examples:**
```caddyfile
# Relative path
php_server {
    worker index.php 2
}

# Absolute path
php_server {
    worker /app/public/index.php 4
}
```

### Incorrect Syntax (Nested Form)

❌ **DO NOT USE** this syntax:

```caddyfile
php_server {
    worker {
        file /app/public/index.php
        num 2
    }
}
```

This nested structure is **not valid** in FrankenPHP and will cause configuration errors.

## Worker Count Recommendations

| Environment | Workers | Rationale |
|-------------|---------|-----------|
| Development | 2 | Lower memory footprint, faster iteration |
| Production | 4-8 | Higher concurrency, better throughput |
| High-Load | 16+ | Maximum concurrency for heavy traffic |

**Formula**: `workers = 2 × CPU_cores` (default)

## Performance Benefits

### Request Persistence
- Application stays loaded in memory between requests
- No need to bootstrap on each request
- Reduced latency and improved response times

### Improved Throughput
- Multiple workers handle concurrent requests
- Better CPU utilization
- Typical improvement: 2-5x throughput increase

### Resource Efficiency
- Reduced memory fragmentation
- Better cache locality
- Lower CPU overhead

## Docker Compose Configuration

```yaml
frankenphp:
  tty: true
  stdin_open: true
  build:
    context: .
    dockerfile: docker/frankenphp/Dockerfile.dev
  volumes:
    - ./:/app
  working_dir: /app
  environment:
    - APP_ENV=dev
    - APP_DEBUG=1
  ports:
    - "8000:80"
```

**Important Settings:**
- `tty: true`: Enables TTY allocation for proper signal handling
- `stdin_open: true`: Allows interactive input
- These settings ensure graceful container shutdown

## Testing Worker Mode

### Start FrankenPHP

```bash
docker-compose -f docker-compose.dev.yml up -d frankenphp
```

### Check Container Status

```bash
docker-compose -f docker-compose.dev.yml ps frankenphp
```

### View Logs

```bash
docker-compose -f docker-compose.dev.yml logs frankenphp --tail=50
```

### Test HTTP Endpoint

```bash
curl -v http://localhost:8000/
```

Expected response:
- Status: 200 OK
- Content-Type: text/html
- Full Symfony welcome page

### Monitor Worker Processes

```bash
docker-compose -f docker-compose.dev.yml exec frankenphp ps aux | grep frankenphp
```

## Troubleshooting

### Container Crashes Immediately

**Symptom**: Container exits with code 2 immediately after starting

**Cause**: Invalid Caddyfile syntax

**Solution**: Verify worker syntax is `worker index.php <num>` (not nested)

### Workers Not Starting

**Symptom**: No worker processes visible, requests fail

**Cause**: Missing or incorrect worker configuration

**Solution**: Add `worker index.php <num>` to `php_server` block

### High Memory Usage

**Symptom**: Container consuming excessive memory

**Cause**: Too many workers or memory leak in application

**Solution**: 
- Reduce worker count
- Profile application for memory leaks
- Monitor per-worker memory usage

### Slow Startup

**Symptom**: Application takes long time to respond to first request

**Cause**: Application not optimized for worker mode

**Solution**:
- Enable OPcache
- Enable APCu
- Optimize autoloader
- Profile application startup

## Production Deployment Checklist

- [ ] Use `Caddyfile` (not `Caddyfile.dev`)
- [ ] Set worker count to `4-8` based on CPU cores
- [ ] Enable compression (gzip, zstd)
- [ ] Use JSON logging format
- [ ] Set `APP_ENV=prod` and `APP_DEBUG=0`
- [ ] Configure health checks
- [ ] Monitor worker memory usage
- [ ] Implement graceful shutdown
- [ ] Use environment variables for config
- [ ] Test with production-like load
- [ ] Set up log aggregation
- [ ] Configure alerting for worker crashes

## Performance Tuning

### Worker Count Optimization

1. **Start with default**: `2 × CPU_cores`
2. **Monitor metrics**: CPU, memory, response time
3. **Adjust based on load**: Increase for high traffic, decrease for memory constraints
4. **Test under load**: Use load testing tools

### Memory Optimization

1. **Enable OPcache**: Compiled PHP bytecode caching
2. **Enable APCu**: Application data caching
3. **Monitor per-worker**: Track memory growth
4. **Implement cleanup**: Periodic worker restart if needed

### Caching Strategy

1. **OPcache**: For PHP code compilation
2. **APCu**: For application data
3. **Redis**: For distributed caching
4. **HTTP Cache**: For static content

## Monitoring and Metrics

### Key Metrics to Monitor

- **Worker Count**: Number of active workers
- **Memory Usage**: Per-worker and total
- **CPU Usage**: Per-worker and total
- **Response Time**: Request latency
- **Throughput**: Requests per second
- **Error Rate**: Failed requests

### Logging

**Development**: Console format with DEBUG level
```
[DEBUG] Request received: GET /
[DEBUG] Response sent: 200 OK
```

**Production**: JSON format with INFO level
```json
{
  "timestamp": "2025-11-11T10:00:00Z",
  "level": "info",
  "message": "Request processed",
  "status": 200,
  "duration_ms": 45
}
```

## References

- [FrankenPHP Official Documentation](https://frankenphp.dev/)
- [Caddy Server Documentation](https://caddyserver.com/)
- [PHP Worker Mode Guide](https://frankenphp.dev/docs/worker/)
- [Performance Tuning](https://frankenphp.dev/docs/performance/)

## Related Documentation

- [Docker Overview](./docker-overview.md)
- [Development Setup](./development.md)
- [Production Deployment](./production.md)
