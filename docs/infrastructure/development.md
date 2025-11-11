# Development Environment

This document describes the development infrastructure setup using FrankenPHP, Redis, and Symfony Messenger.

## Architecture

The development stack includes:
- **FrankenPHP** (PHP 8.4) - Modern PHP server with HTTP/2 support
- **Redis** - Message queue and cache backend
- **Messenger Workers** (2) - Async benchmark execution
- **MariaDB** - Database
- **Mercure** - Real-time updates
- **PHP 5.6-8.5** - Benchmark execution containers

## Quick Start

```bash
# Build the development infrastructure
make dev.build

# Start all services
make dev.up

# Check status
make dev.status

# Run benchmarks
make dev.run test=Loop iterations=10

# View logs
make dev.logs

# Stop services
make dev.down
```

## Configuration

### Environment Variables (`.env.dev`)

```bash
APP_ENV=dev
APP_DEBUG=1
BENCHMARK_CONCURRENCY=2          # Lower than prod (8)
BENCHMARK_TIMEOUT=30
MESSENGER_TRANSPORT_DSN=redis://redis:6379/messages
REDIS_MAXMEMORY=512mb           # Smaller than prod (1GB)
MESSENGER_WORKER_COUNT=2        # Fewer workers than prod (4)
MESSENGER_WORKER_MEMORY_LIMIT=128  # Lower than prod (256M)
```

### PHP Configuration (`docker/frankenphp/php-dev.ini`)

Development-specific settings:
- `display_errors = On` - Show errors in browser
- `opcache.validate_timestamps = 1` - Check for file changes
- `xdebug.mode = debug` - Enable Xdebug
- `memory_limit = 512M`
- `error_reporting = E_ALL`

### Caddy Configuration (`docker/frankenphp/Caddyfile.dev`)

- Log level: `DEBUG` (vs `INFO` in prod)
- Log format: `console` (vs `json` in prod)

## Services

### FrankenPHP
- **Port:** 8000
- **Features:**
  - HTTP/2 support
  - Xdebug enabled
  - OPcache with validation
  - Docker socket access for benchmark execution
- **Access:** http://localhost:8000

### Redis
- **Port:** 6379
- **Memory:** 512MB (no persistence)
- **Policy:** allkeys-lru
- **Usage:**
  - Messenger queue
  - Session storage
  - Cache backend

### Messenger Workers
- **Count:** 2 workers
- **Memory:** 128MB per worker
- **Time limit:** 3600s (1 hour)
- **Verbosity:** -vv (debug mode)
- **Auto-restart:** yes

### MariaDB
- **Port:** 3306
- **Database:** php_benchmark
- **User:** php_user / php_password
- **Root:** root / password

### Mercure
- **Port:** 3000
- **URL:** http://localhost:3000/.well-known/mercure
- **Features:** Real-time updates for benchmark progress

## Development vs Production

| Feature | Development | Production |
|---------|------------|------------|
| **Concurrency** | 2 | 8 |
| **Workers** | 2 | 4 |
| **Worker Memory** | 128M | 256M |
| **Redis Memory** | 512MB | 1GB |
| **Redis Persistence** | No | Yes (AOF) |
| **OPcache Validation** | Enabled | Disabled |
| **Xdebug** | Enabled | Disabled |
| **Error Display** | On | Off |
| **Log Level** | DEBUG | INFO |
| **Log Format** | Console | JSON |

## Debugging

### Xdebug

Xdebug is pre-configured and enabled:
- Mode: `debug`
- Port: `9003`
- Host: `host.docker.internal`

**VS Code configuration (`.vscode/launch.json`):**
```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/app": "${workspaceFolder}"
      }
    }
  ]
}
```

**PhpStorm:**
1. Settings → PHP → Debug → Xdebug → Port: `9003`
2. Add Server: Name: `docker`, Host: `localhost`, Port: `8000`
3. Path mapping: `/app` → `/path/to/php_benchmark`

### View Logs

```bash
# FrankenPHP logs
make dev.logs

# Worker logs
docker-compose -f docker-compose.dev.yml logs -f messenger-worker-1

# Redis logs
docker-compose -f docker-compose.dev.yml logs -f redis

# All logs
docker-compose -f docker-compose.dev.yml logs -f
```

### Monitor Workers

```bash
# Check worker status
make dev.status

# Restart workers
docker-compose -f docker-compose.dev.yml restart messenger-worker-1 messenger-worker-2

# View worker output
docker-compose -f docker-compose.dev.yml logs -f messenger-worker-1 messenger-worker-2
```

### Redis Monitoring

```bash
# Connect to Redis CLI
docker-compose -f docker-compose.dev.yml exec redis redis-cli

# Monitor commands
docker-compose -f docker-compose.dev.yml exec redis redis-cli MONITOR

# Check queue length
docker-compose -f docker-compose.dev.yml exec redis redis-cli LLEN messages

# View memory usage
docker-compose -f docker-compose.dev.yml exec redis redis-cli INFO memory
```

## Common Tasks

### Running Benchmarks

```bash
# Single benchmark
make dev.run test=Loop iterations=10

# All benchmarks (1 iteration)
make dev.run iterations=1

# Specific PHP version
make dev.run test=Loop iterations=10 version=php84
```

### Database Operations

```bash
# Reset database (empty)
make db.reset

# Reset and load fixtures
make db.refresh

# Run migrations
docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console d:m:m
```

### Asset Compilation

```bash
# Compile assets
make assets.refresh

# Clear cache
docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console cache:clear
```

### Code Quality

```bash
# Run all quality checks
make quality

# Fix code style
make phpcsfixer-fix

# Run static analysis
make phpstan

# Run tests
make test
```

## Performance Testing

Compare development vs production performance:

```bash
# Development (2 workers, 2 concurrency)
make dev.run test=Loop iterations=100

# Production (4 workers, 8 concurrency)
make prod.run test=Loop iterations=100
```

Expected results:
- **Development:** ~8-10s for 100 iterations
- **Production:** ~4-5s for 100 iterations

## Troubleshooting

### Workers not consuming messages

```bash
# Check worker status
make dev.status

# Restart workers
docker-compose -f docker-compose.dev.yml restart messenger-worker-1 messenger-worker-2

# Check logs for errors
docker-compose -f docker-compose.dev.yml logs messenger-worker-1
```

### Redis connection issues

```bash
# Check Redis is running
docker-compose -f docker-compose.dev.yml ps redis

# Test Redis connection
docker-compose -f docker-compose.dev.yml exec redis redis-cli ping

# Check FrankenPHP can reach Redis
docker-compose -f docker-compose.dev.yml exec frankenphp php -r "var_dump((new Redis())->connect('redis', 6379));"
```

### Benchmarks timing out

Check configuration:
```bash
# View current timeout
docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console debug:container --parameter=env\(BENCHMARK_TIMEOUT\)

# Increase timeout in .env.dev
BENCHMARK_TIMEOUT=60
```

### OPcache not updating

Development mode validates timestamps every 2 seconds. If changes don't appear:
```bash
# Clear OPcache
docker-compose -f docker-compose.dev.yml exec frankenphp php -r "opcache_reset();"

# Restart FrankenPHP
docker-compose -f docker-compose.dev.yml restart frankenphp
```

## Architecture Differences

### Messenger Workers

**Development** uses standalone worker containers:
```yaml
messenger-worker-1:
  command: php bin/console messenger:consume async --time-limit=3600 --memory-limit=128M -vv
```

**Production** uses Supervisor inside FrankenPHP container:
```ini
[program:messenger-worker-%(process_num)02d]
command=php /app/bin/console messenger:consume async --time-limit=3600 --memory-limit=256M
numprocs=4
```

### Why Separate Workers in Dev?

1. **Easier debugging** - Each worker has its own logs
2. **Independent restarts** - Can restart workers without affecting web server
3. **Lower resource usage** - Only 2 workers vs 4 in prod
4. **Clearer separation** - Better visibility of what each component does

## Next Steps

1. **Profiling:** Use Blackfire or Xdebug profiler for performance analysis
2. **Testing:** Add integration tests for benchmark execution
3. **Monitoring:** Add Prometheus metrics for worker performance
4. **Documentation:** Document custom benchmarks creation process

## See Also

- [Production Infrastructure](PRODUCTION.md) - Production setup with 4 workers
- [Architecture Documentation](docs/architecture/01-overview.md) - Clean Architecture overview
- [Creating Benchmarks](docs/guides/creating-benchmarks.md) - How to add new benchmarks
- [Agent Guidelines](AGENTS.md) - Commands for AI coding agents
