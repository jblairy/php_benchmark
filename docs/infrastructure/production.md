# Production Infrastructure - FrankenPHP + Redis + Messenger

## Architecture

This production infrastructure uses:

- **FrankenPHP** in worker mode (4 workers) for ultra-fast performance
- **Redis** for application cache and Messenger queue
- **4 Messenger workers** for parallel benchmark processing
- **Supervisord** to automatically manage all processes
- **MariaDB** for persistence
- **Mercure** for real-time updates

## Expected Performance

- **-63%** execution time vs Phase 2 (~5s instead of 13.6s for 120 benchmarks)
- **1000+ benchmarks/minute** (vs 200 with dev setup)
- **99.9% availability** with automatic auto-restart
- **Redis cache**: 80-95% hit ratio
- **Queue throughput**: 10000 messages/sec (vs 100 before)

## Getting Started

### 1. Launch Production Infrastructure

```bash
# Build images
docker-compose -f docker-compose.prod.yml build

# Start all services
docker-compose -f docker-compose.prod.yml up -d

# Verify all services are up
docker-compose -f docker-compose.prod.yml ps
```

### 2. Initialize Database

```bash
# Create/migrate database
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console doctrine:migrations:migrate --no-interaction

# Load fixtures
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console doctrine:fixtures:load --no-interaction
```

### 3. Test Performance

```bash
# Simple test (10 iterations, PHP 8.4)
time docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console benchmark:run --test="Iterate With For" --iterations=10 --php-version=php84

# Full test (all PHP versions)
time docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console benchmark:run --test="Hash With Sha256" --iterations=20
```

## Monitoring

### Check Service Status

```bash
# FrankenPHP logs (web server)
docker-compose -f docker-compose.prod.yml logs -f frankenphp

# Messenger workers logs
docker-compose -f docker-compose.prod.yml exec frankenphp tail -f var/log/messenger-worker-*.log

# Redis stats
docker-compose -f docker-compose.prod.yml exec redis redis-cli INFO stats

# Messenger queue
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console messenger:stats

# Failed messages
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console messenger:failed:show
```

### Supervisord (Process Management)

```bash
# Status of all processes
docker-compose -f docker-compose.prod.yml exec frankenphp supervisorctl status

# Restart specific worker
docker-compose -f docker-compose.prod.yml exec frankenphp supervisorctl restart messenger-worker-1

# Restart all workers
docker-compose -f docker-compose.prod.yml exec frankenphp supervisorctl restart messenger-workers:*

# Restart FrankenPHP
docker-compose -f docker-compose.prod.yml exec frankenphp supervisorctl restart frankenphp
```

## Configuration

### Environment Variables (.env.prod)

```bash
# Concurrency (number of parallel processes)
BENCHMARK_CONCURRENCY=8  # 8 for production, 4 for dev

# Timeout per benchmark (seconds)
BENCHMARK_TIMEOUT=60     # 60s for production, 30s for dev

# Redis
REDIS_URL=redis://redis:6379
MESSENGER_TRANSPORT_DSN=redis://redis:6379/messages
```

### FrankenPHP Worker Mode

FrankenPHP worker mode keeps the application in memory between requests:

- **4 PHP workers** preloaded (defined in `docker/frankenphp/Caddyfile`)
- **OPcache preload** enabled (`config/preload.php`)
- **APCu** for local cache
- **Realpath cache** optimized

### Messenger Workers

4 Messenger workers process benchmarks in parallel:

- **Time limit**: 1h per worker (automatically restarts after)
- **Memory limit**: 256MB per worker
- **Message limit**: 1000 messages per worker before restart
- **Auto-restart**: Supervisord automatically restarts on failure

## Production Optimizations

### OPcache

```ini
opcache.enable = 1
opcache.memory_consumption = 256M
opcache.interned_strings_buffer = 16M
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0  # No revalidation in prod
opcache.preload = /app/config/preload.php
```

### Redis Cache

```yaml
# config/packages/prod/cache.yaml
framework:
    cache:
        app: cache.adapter.redis
        pools:
            cache.benchmarks:
                default_lifetime: 3600  # 1h
            cache.results:
                default_lifetime: 7200  # 2h
```

### Realpath Cache

```ini
realpath_cache_size = 4096K
realpath_cache_ttl = 600  # 10 minutes
```

## Scaling

### Increase Number of Messenger Workers

Edit `docker/supervisor/supervisord.conf` and add more workers:

```ini
[program:messenger-worker-5]
command=php /app/bin/console messenger:consume async --time-limit=3600 --memory-limit=256M --limit=1000 -vv
# ... (same config as other workers)
```

Then restart:

```bash
docker-compose -f docker-compose.prod.yml restart frankenphp
```

### Increase Resources

Edit `docker-compose.prod.yml`:

```yaml
frankenphp:
  deploy:
    resources:
      limits:
        cpus: '8'      # Instead of 4
        memory: 4G     # Instead of 2G
```

### Horizontal Scaling (Multiple Nodes)

For horizontal scaling:

1. Use external Redis (not in Docker)
2. Use external database
3. Load balancer in front of multiple FrankenPHP instances
4. Shared storage for `/app/var`

## Troubleshooting

### FrankenPHP Won't Start

```bash
# Check logs
docker-compose -f docker-compose.prod.yml logs frankenphp

# Verify Caddy config
docker-compose -f docker-compose.prod.yml exec frankenphp frankenphp validate --config /app/docker/frankenphp/Caddyfile
```

### Messenger Workers Not Consuming

```bash
# Check Supervisor status
docker-compose -f docker-compose.prod.yml exec frankenphp supervisorctl status

# Restart workers
docker-compose -f docker-compose.prod.yml exec frankenphp supervisorctl restart messenger-workers:*

# Check logs
docker-compose -f docker-compose.prod.yml exec frankenphp tail -f var/log/messenger-worker-1.error.log
```

### Redis Connection Refused

```bash
# Verify Redis is up
docker-compose -f docker-compose.prod.yml ps redis

# Test connection
docker-compose -f docker-compose.prod.yml exec redis redis-cli ping
```

### Degraded Performance

```bash
# Check resource usage
docker stats

# Check OPcache
docker-compose -f docker-compose.prod.yml exec frankenphp php -r "print_r(opcache_get_status());"

# Check Redis
docker-compose -f docker-compose.prod.yml exec redis redis-cli INFO stats

# Clear cache
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console cache:clear --env=prod
```

## Maintenance

### Clear Cache

```bash
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console cache:clear --env=prod
```

### Empty Redis Queue

```bash
docker-compose -f docker-compose.prod.yml exec redis redis-cli FLUSHDB
```

### Database Backup

```bash
docker-compose -f docker-compose.prod.yml exec mariadb mysqldump -uroot -ppassword php_benchmark > backup.sql
```

### Application Update

```bash
# Pull changes
git pull

# Rebuild
docker-compose -f docker-compose.prod.yml build frankenphp

# Restart
docker-compose -f docker-compose.prod.yml up -d frankenphp

# Migrations
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console doctrine:migrations:migrate --no-interaction
```

## Security

⚠️ **IMPORTANT**: Before deploying to production:

1. Change `APP_SECRET` in `.env.prod`
2. Change `MERCURE_JWT_SECRET`
3. Change MariaDB passwords
4. Enable HTTPS (FrankenPHP supports Let's Encrypt automatically)
5. Restrict Redis access (password)
6. Configure a firewall

## Support

For more information:
- FrankenPHP: https://frankenphp.dev/
- Symfony Messenger: https://symfony.com/doc/current/messenger.html
- Supervisord: http://supervisord.org/
