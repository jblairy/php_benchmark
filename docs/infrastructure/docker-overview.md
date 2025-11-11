# Docker Setup Overview

## File Structure

```
/
├── docker-compose.yml           # Base configuration (legacy, used for basic setup)
├── docker-compose.dev.yml       # Development mode (extends base)
├── docker-compose.prod.yml      # Production mode (extends base)
├── Dockerfile.main              # Main PHP 8.4 container
├── Dockerfile.php85             # Custom PHP 8.5 build (no official image yet)
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

### Base Configuration (`docker-compose.yml`)
- **Purpose**: Basic services setup
- **Services**: main, mariadb, mercure, php56-php85
- **Use case**: Legacy/simple development
- **Command**: `docker-compose up`

### Development Mode (`docker-compose.dev.yml`)
- **Purpose**: Development with hot reload and debugging
- **Key services**: 
  - `frankenphp` (with Xdebug)
  - `redis` (message queue)
  - `messenger-worker-1`, `messenger-worker-2` (separate containers)
- **Configuration**:
  - Xdebug enabled
  - OPcache validation on
  - 2 workers (lower resource usage)
  - Debug logging
- **Command**: `docker-compose -f docker-compose.dev.yml up`
- **Port**: 8000
- **Documentation**: [development.md](development.md)

### Production Mode (`docker-compose.prod.yml`)
- **Purpose**: High-performance production environment
- **Key services**:
  - `frankenphp` (with Supervisord managing 4 workers internally)
  - `redis` (with persistence)
- **Configuration**:
  - OPcache preload enabled
  - 4 Messenger workers (managed by Supervisord)
  - 8 concurrent benchmark processes
  - JSON logging
- **Command**: `docker-compose -f docker-compose.prod.yml up`
- **Port**: 8000
- **Documentation**: [production.md](production.md)

## Dockerfiles

### `Dockerfile.main`
- **Purpose**: Main PHP 8.4 container for orchestration
- **Base**: `php:8.4-cli`
- **Used in**: `docker-compose.yml` (base mode)
- **Contains**: PHP 8.4 + Docker CLI + Composer

### `Dockerfile.php85`
- **Purpose**: Custom PHP 8.5 build for benchmarks
- **Why needed**: PHP 8.5 doesn't have official Docker image yet
- **Base**: Built from source or beta image
- **Used in**: All compose files for benchmark execution

### `docker/frankenphp/Dockerfile`
- **Purpose**: Production FrankenPHP server
- **Features**: Worker mode, OPcache preload, Supervisord
- **Used in**: `docker-compose.prod.yml`

### `docker/frankenphp/Dockerfile.dev`
- **Purpose**: Development FrankenPHP server
- **Features**: Xdebug, hot reload, debug logging
- **Used in**: `docker-compose.dev.yml`

## Service Architecture

### Common Services (All Modes)
- **mariadb**: Database (MariaDB 10.11)
- **mercure**: Real-time updates (Server-Sent Events)
- **php56-php85**: Benchmark execution containers (isolated environments)

### Development-Specific Services
- **frankenphp**: Web server with Xdebug
- **redis**: Message queue (no persistence)
- **messenger-worker-1/2**: Async workers (2 separate containers)

### Production-Specific Services
- **frankenphp**: Web server + Supervisord (4 workers internal)
- **redis**: Message queue (with AOF persistence)

## Key Differences: Dev vs Prod

| Feature | Development | Production |
|---------|-------------|------------|
| **Server** | FrankenPHP (debug) | FrankenPHP (worker mode) |
| **Workers** | 2 (separate containers) | 4 (Supervisord managed) |
| **Concurrency** | 2 | 8 |
| **Xdebug** | Enabled | Disabled |
| **OPcache** | Validation on | Preload enabled |
| **Redis** | No persistence | AOF persistence |
| **Logging** | Console (verbose) | JSON (structured) |
| **Resources** | Lower limits | Higher limits |

## Quick Start Commands

### Development
```bash
# Start development environment
docker-compose -f docker-compose.dev.yml up -d

# View logs
docker-compose -f docker-compose.dev.yml logs -f

# Run benchmark
docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console benchmark:run
```

### Production
```bash
# Start production environment
docker-compose -f docker-compose.prod.yml up -d

# Check worker status
docker-compose -f docker-compose.prod.yml exec frankenphp supervisorctl status

# Run benchmark
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console benchmark:run
```

### Base (Legacy)
```bash
# Start base environment
docker-compose up -d

# Run command
docker-compose run --rm main php bin/console benchmark:run
```

## Makefile Shortcuts

The `Makefile` provides convenient shortcuts:

```bash
# Development
make dev.up          # Start dev environment
make dev.logs        # View dev logs
make dev.run         # Run benchmarks in dev

# Production
make prod.up         # Start prod environment
make prod.status     # Check worker status
make prod.run        # Run benchmarks in prod

# Database
make db.reset        # Drop and recreate database
make db.refresh      # Reset + load fixtures
```

## See Also

- **[development.md](development.md)** - Development environment details
- **[production.md](production.md)** - Production infrastructure guide
- **[docker.md](docker.md)** - Docker architecture deep dive
- **[../README.md](../README.md)** - Main documentation index
