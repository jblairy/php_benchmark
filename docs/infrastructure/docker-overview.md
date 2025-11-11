# Docker Setup Overview

## File Structure

```
/
├── docker-compose.dev.yml       # Development mode
├── docker-compose.ci.yml        # CI/CD mode (tests, quality checks)
├── docker-compose.prod.yml      # Production mode
├── Dockerfile.php85             # Custom PHP 8.5 build (no official image yet)
├── .env.dev                     # Development environment variables
├── .env.test                    # Test/CI environment variables
├── .env.prod                    # Production environment variables
└── docker/
    ├── frankenphp/
    │   ├── Dockerfile           # FrankenPHP production
    │   ├── Dockerfile.dev       # FrankenPHP development (with Xdebug)
    │   ├── Caddyfile            # Production Caddy config
    │   ├── Caddyfile.dev        # Development Caddy config
    │   ├── php.ini              # Production PHP config
    │   └── php-dev.ini          # Development PHP config (Xdebug enabled)
    └── supervisor/
        └── supervisord.conf     # Process manager for production workers
```

## Docker Compose Modes

### Development Mode (`docker-compose.dev.yml`)
- **Purpose**: Local development with hot reload and debugging
- **Key services**: 
  - `frankenphp` (with Xdebug)
  - `redis` (message queue, no persistence)
  - `messenger-worker-1`, `messenger-worker-2` (separate containers)
  - `mariadb`, `mercure`, `php56-php85` (benchmarks)
- **Configuration**:
  - Xdebug enabled (port 9003)
  - OPcache validation on (hot reload)
  - 2 Messenger workers (lower resource usage)
  - Console logging (debug)
  - Concurrency: 2
- **Command**: `make dev.up` or `docker-compose -f docker-compose.dev.yml up -d`
- **Port**: 8000
- **Documentation**: [development.md](development.md)

### CI/CD Mode (`docker-compose.ci.yml`)
- **Purpose**: Automated testing and quality checks (similar to prod)
- **Key services**:
  - `frankenphp` (production-like, lighter resources)
  - `redis` (no persistence, 512MB)
  - `mariadb` (in-memory tmpfs for speed)
  - `mercure`, `php56-php85` (benchmarks)
- **Configuration**:
  - Production Dockerfile (FrankenPHP worker mode)
  - No Xdebug (faster execution)
  - Health checks with short intervals
  - Lighter resource limits (CI runners)
  - Concurrency: 4
  - Database: in-memory (tmpfs)
- **Command**: `make ci.up` or `docker-compose -f docker-compose.ci.yml up -d`
- **Port**: 8000
- **Use case**: GitHub Actions, GitLab CI, local CI simulation

### Production Mode (`docker-compose.prod.yml`)
- **Purpose**: High-performance production environment
- **Key services**:
  - `frankenphp` (with Supervisord managing 4 workers internally)
  - `redis` (with AOF persistence, 1GB)
  - `mariadb`, `mercure`, `php56-php85` (benchmarks)
- **Configuration**:
  - OPcache preload enabled
  - 4 Messenger workers (managed by Supervisord)
  - 8 concurrent benchmark processes
  - JSON logging
  - Higher resource limits
- **Command**: `make prod.up` or `docker-compose -f docker-compose.prod.yml up -d`
- **Port**: 8000
- **Documentation**: [production.md](production.md)

## Dockerfiles

### `Dockerfile.php85`
- **Purpose**: Custom PHP 8.5 build for benchmarks
- **Why needed**: PHP 8.5 doesn't have official Docker image yet
- **Base**: Built from PHP 8.5 RC or beta
- **Used in**: All compose files for PHP 8.5 benchmark execution

### `docker/frankenphp/Dockerfile`
- **Purpose**: Production FrankenPHP server
- **Features**: Worker mode, OPcache preload, Supervisord
- **Used in**: `docker-compose.prod.yml` and `docker-compose.ci.yml`

### `docker/frankenphp/Dockerfile.dev`
- **Purpose**: Development FrankenPHP server
- **Features**: Xdebug, hot reload, debug logging
- **Used in**: `docker-compose.dev.yml`

## Service Architecture

### Common Services (All Modes)
- **frankenphp**: Web server + PHP application
- **mariadb**: Database (MariaDB 10.11)
- **mercure**: Real-time updates (Server-Sent Events)
- **redis**: Message queue + cache
- **php56-php85**: Benchmark execution containers (isolated environments)

### Development-Specific Services
- **messenger-worker-1/2**: Async workers (2 separate containers for easier debugging)

### Production-Specific Services
- **Supervisord**: Manages 4 Messenger workers internally in FrankenPHP container

## Key Differences: Dev vs CI vs Prod

| Feature | Development | CI/CD | Production |
|---------|-------------|-------|------------|
| **Server** | FrankenPHP (debug) | FrankenPHP (prod build) | FrankenPHP (worker mode) |
| **Workers** | 2 (separate containers) | 2 (internal) | 4 (Supervisord) |
| **Concurrency** | 2 | 4 | 8 |
| **Xdebug** | Enabled | Disabled | Disabled |
| **OPcache** | Validation on | Preload | Preload |
| **Redis** | 512MB, no persistence | 512MB, no persistence | 1GB, AOF persistence |
| **Database** | Disk | In-memory (tmpfs) | Disk |
| **Logging** | Console (verbose) | Structured | JSON (structured) |
| **Resources** | Lower (dev-friendly) | Moderate (CI limits) | Higher (production) |
| **Health Checks** | 10s intervals | 5s intervals | 10s intervals |

## Quick Start Commands

### Development
```bash
# Start development environment
make dev.up
# or
docker-compose -f docker-compose.dev.yml up -d

# View logs
make dev.logs

# Run benchmark
make run test=Loop iterations=10
```

### CI/CD
```bash
# Start CI environment
make ci.up

# Run tests
make ci.test

# Run quality checks
make ci.quality

# Clean up (with volumes)
make ci.down
```

### Production
```bash
# Start production environment
make prod.up

# Check worker status
make prod.status

# Run benchmark
make prod.run test=Loop iterations=100
```

## Makefile Shortcuts

The `Makefile` provides convenient shortcuts:

```bash
# Development
make up              # Start dev environment (alias)
make dev.up          # Start dev environment
make dev.build       # Build dev images
make dev.logs        # View dev logs
make dev.status      # Check services + workers
make dev.run         # Run benchmarks in dev

# CI/CD
make ci.up           # Start CI environment
make ci.build        # Build CI images
make ci.test         # Run PHPUnit tests
make ci.quality      # Run quality checks (PHPStan, CS-Fixer, PHPMD)
make ci.down         # Stop and clean CI environment

# Production
make prod.up         # Start prod environment
make prod.build      # Build prod images
make prod.status     # Check worker status
make prod.run        # Run benchmarks in prod

# Database (uses dev environment)
make db.reset        # Drop and recreate database
make db.refresh      # Reset + load fixtures

# Quality (uses dev environment)
make quality         # Run all quality checks
make test            # Run PHPUnit tests
```

## See Also

- **[development.md](development.md)** - Development environment details
- **[production.md](production.md)** - Production infrastructure guide
- **[docker.md](docker.md)** - Docker architecture deep dive
- **[../README.md](../README.md)** - Main documentation index
