# Docker Infrastructure

This document describes the Docker architecture used in the PHP Benchmark project.

## Table of Contents

1. [Overview](#overview)
2. [Architecture Diagram](#architecture-diagram)
3. [Services](#services)
4. [Resource Constraints](#resource-constraints)
5. [Networking](#networking)
6. [Volumes](#volumes)
7. [Execution Flow](#execution-flow)
8. [Dockerfiles](#dockerfiles)
9. [Real-Time Updates](#real-time-updates)

## Overview

The PHP Benchmark project uses a multi-container Docker architecture to:

- **Isolate PHP versions**: Each PHP version runs in its own container for accurate benchmarking
- **Ensure fair testing**: Resource limits (CPU, memory) prevent version-specific bias
- **Manage dependencies**: The main container orchestrates benchmark execution across all PHP containers
- **Persist results**: MariaDB stores benchmark results for the web dashboard
- **Enable real-time updates**: Mercure broadcasts benchmark progress to the web interface

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         Host Machine                             │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                    Docker Network                           │ │
│  │                                                              │ │
│  │  ┌──────────────┐         ┌─────────────┐  ┌────────────┐ │ │
│  │  │   main       │────────▶│  mariadb    │  │  mercure   │ │ │
│  │  │  (PHP 8.4)   │         │ (MariaDB    │  │  (SSE Hub) │ │ │
│  │  │              │         │  10.11)     │  │ Port 3000  │ │ │
│  │  │ - Web server │         │             │  │            │ │ │
│  │  │ - CLI        │         └─────────────┘  └──────▲─────┘ │ │
│  │  │ - Orchestrator│              │                  │       │ │
│  │  └──────┬───────┘              │                  │       │ │
│  │         │                       │          Publishes       │ │
│  │         │ docker-compose exec   │          Updates         │ │
│  │         ▼                       ▼                  │       │ │
│  │  ┌─────────────────────────────────────────────┐  │       │ │
│  │  │     Benchmark Execution Containers          │──┘       │ │
│  │  │                                              │          │ │
│  │  │  ┌────────┐ ┌────────┐ ┌────────┐ ┌───────┐│          │ │
│  │  │  │ php56  │ │ php70  │ │ php71  │ │ ...   ││          │ │
│  │  │  └────────┘ └────────┘ └────────┘ └───────┘│          │ │
│  │  │  ┌────────┐ ┌────────┐ ┌────────┐ ┌───────┐│          │ │
│  │  │  │ php80  │ │ php81  │ │ php82  │ │ php83 ││          │ │
│  │  │  └────────┘ └────────┘ └────────┘ └───────┘│          │ │
│  │  │  ┌────────┐ ┌────────┐                      │          │ │
│  │  │  │ php84  │ │ php85  │                      │          │ │
│  │  │  └────────┘ └────────┘                      │          │ │
│  │  │                                              │          │ │
│  │  │  All containers: 512MB RAM, 1 CPU core      │          │ │
│  │  └─────────────────────────────────────────────┘          │ │
│  │                                                              │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │ SSE (Server-Sent Events)
                              │
                        Browser Clients
```

## Services

### 1. Main Service (`main`)

**Purpose**: Primary application container running Symfony 7.3 on PHP 8.4

**Key responsibilities**:
- Serve the web dashboard (port 8000)
- Execute CLI commands (`bin/console`)
- Orchestrate benchmark execution across PHP containers
- Manage database migrations and ORM operations

**Image**: Custom `Dockerfile.main` based on `php:8.4-cli`

**Exposed ports**: `8000:8000` (web server)

**Environment variables**:
- `DOCKER_HOST=unix:///var/run/docker.sock` - Access to Docker daemon
- `DATABASE_URL=mysql://root:password@mariadb:3306/php_benchmark`

**Volumes**:
- `./:/srv/php_benchmark` - Project source code (bind mount)
- `/var/run/docker.sock:/var/run/docker.sock` - Docker socket for orchestration

**Command**: `php -S 0.0.0.0:8000 -t public` (built-in PHP web server)

---

### 2. MariaDB Service (`mariadb`)

**Purpose**: Relational database for storing benchmark results

**Image**: Official `mariadb:10.11`

**Exposed ports**: `3306:3306` (MySQL protocol)

**Environment variables**:
- `MYSQL_ROOT_PASSWORD=password`
- `MYSQL_DATABASE=php_benchmark`
- `MYSQL_USER=php_user`
- `MYSQL_PASSWORD=php_password`

**Volumes**:
- `mariadb_data:/var/lib/mysql` - Named volume for data persistence

**Character set**: UTF-8 (`utf8mb4_unicode_ci`)

**Restart policy**: `unless-stopped`

---

### 3. PHP Benchmark Containers (`php56` → `php85`)

**Purpose**: Isolated environments for running benchmarks on different PHP versions

**Supported versions**:
- **PHP 5.6** → **PHP 8.4**: Official Docker Hub images (`php:X.Y-cli`)
- **PHP 8.5**: Custom build from source (alpha version)

**Common configuration**:
- **Working directory**: `/srv/php_benchmark`
- **Volume**: `./:/srv/php_benchmark` (shared codebase)
- **Memory limit**: 512 MB
- **CPU limit**: 1 core
- **Command**: `tail -f /dev/null` (keeps container running in idle state)

**Why idle state?**

The containers run `tail -f /dev/null` to stay alive without consuming resources. The main container executes benchmarks on-demand using:

```bash
docker-compose exec -T <php_version> php <benchmark_script>.php
```

This approach:
- ✅ Avoids overhead of starting/stopping containers repeatedly
- ✅ Provides instant benchmark execution
- ✅ Simplifies orchestration logic

---

## Resource Constraints

All PHP benchmark containers have identical resource limits to ensure fair performance comparison:

| Resource | Limit | Reason |
|----------|-------|--------|
| **Memory** | 512 MB | Prevents memory-intensive benchmarks from skewing results |
| **CPU** | 1 core | Ensures consistent CPU availability across tests |

**Example configuration** (from `docker-compose.yml`):

```yaml
php84:
  image: php:8.4-cli
  mem_limit: 512m
  cpus: 1
```

**Why these limits?**

- **Fairness**: Prevents newer PHP versions from benefiting from more resources
- **Reproducibility**: Consistent environment across benchmark runs
- **Isolation**: Ensures one benchmark doesn't starve others

---

## Networking

**Default network**: Docker Compose creates a bridge network where all services can communicate using service names as hostnames.

**Service discovery**:
- `main` → `mariadb` (database connection)
- `main` → `php56`, `php70`, ..., `php85` (benchmark execution)

**Port exposure**:
- `main:8000` → `localhost:8000` (web dashboard)
- `mariadb:3306` → `localhost:3306` (database access for external tools)

---

## Volumes

### Named Volume: `mariadb_data`

**Purpose**: Persist MariaDB data across container restarts

**Location**: Managed by Docker (usually `/var/lib/docker/volumes/`)

**Lifecycle**: Persists even if containers are removed

**Backup recommendation**:

```bash
# Export database
docker-compose exec mariadb mysqldump -u root -ppassword php_benchmark > backup.sql

# Restore database
docker-compose exec -T mariadb mysql -u root -ppassword php_benchmark < backup.sql
```

### Bind Mount: `./:/srv/php_benchmark`

**Purpose**: Share project code between host and containers

**Benefits**:
- Hot-reload during development
- Benchmark scripts accessible to all PHP containers
- Temporary files shared across services

**Mounted on**:
- `main` container
- All PHP benchmark containers (`php56` → `php85`)

---

## Execution Flow

### 1. Starting the Environment

```bash
make up  # or: docker-compose up -d
```

**What happens**:
1. Build custom images (`Dockerfile.main`, `Dockerfile.php85`)
2. Pull official PHP images (php:5.6-cli → php:8.4-cli)
3. Start MariaDB and wait for readiness
4. Start main container (depends on MariaDB)
5. Start all PHP benchmark containers in idle state

### 2. Running a Benchmark

**User executes**:

```bash
docker-compose run --rm main php bin/console benchmark:run --test=Loop --php-version=php84 --iterations=10
```

**Execution flow**:

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. CLI Command (main container)                                 │
│    bin/console benchmark:run --test=Loop --php-version=php84    │
└────────────┬───────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. Application Layer (Symfony)                                  │
│    BenchmarkCommand → ExecuteBenchmarkUseCase                   │
└────────────┬───────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. Domain Layer                                                 │
│    - BenchmarkOrchestrator: coordinates execution               │
│    - CodeExtractor: extracts benchmark code                     │
│    - ScriptBuilder: generates executable PHP script             │
└────────────┬───────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. Infrastructure Layer - DockerScriptExecutor                  │
│    - Creates temp file: /srv/php_benchmark/var/tmp/script.php   │
│    - Executes: docker-compose exec -T php84 php <script.php>    │
│    - Parses JSON output                                         │
└────────────┬───────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. Target PHP Container (php84)                                 │
│    - Runs benchmark script                                      │
│    - Outputs JSON: {"avg": 0.0012, "p90": 0.0015, ...}          │
└────────────┬───────────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────────────┐
│ 6. Persistence Layer                                            │
│    - DoctrinePulseResultPersister saves to MariaDB              │
│    - Stores: benchmark_id, php_version, metrics, timestamp      │
└─────────────────────────────────────────────────────────────────┘
```

**Key implementation** (DockerScriptExecutor:48):

```php
private function executeInDocker(string $phpVersion, string $scriptPath): string
{
    $command = sprintf(
        'docker-compose exec -T %s php %s 2>&1',
        escapeshellarg($phpVersion),
        escapeshellarg($scriptPath),
    );

    exec($command, $output, $exitCode);
    // ...
}
```

### 3. Viewing Results

**Web dashboard**:

```bash
# Navigate to: http://localhost:8000
```

**Direct database query**:

```bash
docker-compose exec mariadb mysql -u root -ppassword php_benchmark -e "SELECT * FROM benchmark_results ORDER BY created_at DESC LIMIT 10;"
```

---

## Dockerfiles

### Dockerfile.main

**Purpose**: Main application container with development tools

**Base image**: `php:8.4-cli`

**Installed packages**:
- System libraries: `libzip-dev`, `default-mysql-client`, `git`, `curl`
- PHP extensions: `pdo_mysql`, `zip` (via PIE)
- Tools: `composer`, `docker-compose`, `PIE` (PHP Installer for Extensions)

**Why docker-compose inside a container?**

The main container needs to orchestrate other containers, so it runs `docker-compose exec` commands. The Docker socket bind mount (`/var/run/docker.sock`) enables this.

**Build command**:

```bash
docker-compose build main
```

**File**: `Dockerfile.main`

---

### Dockerfile.php85

**Purpose**: Custom PHP 8.5 alpha build from source

**Base image**: `debian:bullseye`

**Why custom build?**

PHP 8.5 is not yet officially released, so there's no Docker Hub image. This Dockerfile compiles PHP 8.5.0 Alpha 1 from source.

**Build process**:
1. Install build dependencies (`build-essential`, `libxml2-dev`, etc.)
2. Download PHP 8.5.0 Alpha 1 tarball from php.net
3. Configure with:
   - OpenSSL support
   - Mbstring (multibyte strings)
   - SOAP extension
   - Intl (internationalization)
4. Compile with `make -j$(nproc)` (parallel build)
5. Install to `/usr/local/php8.5`
6. Add to `PATH`

**Build command**:

```bash
docker-compose build php85
```

**File**: `Dockerfile.php85`

**Note**: This image will need updating when PHP 8.5 reaches stable release.

---

## Real-Time Updates

### Mercure Service

**Purpose**: Real-time Server-Sent Events (SSE) hub for broadcasting benchmark progress

**Image**: Official `dunglas/mercure`

**Exposed ports**: `3000:80` (HTTP, HTTPS disabled for development)

**Environment variables**:
- `SERVER_NAME: ':80'` - HTTP mode (no HTTPS)
- `MERCURE_PUBLISHER_JWT_KEY` - Authentication for publishing updates
- `MERCURE_SUBSCRIBER_JWT_KEY` - Authentication for subscribers
- `MERCURE_EXTRA_DIRECTIVES` - CORS and anonymous access configuration

**Volumes**:
- `mercure_data:/data` - Persistent data
- `mercure_config:/config` - Configuration files

**How it works**:

1. **Backend publishes**: When benchmarks run, Symfony publishes progress events to Mercure
   ```php
   $hub->publish(new Update('benchmark/progress', json_encode($data)));
   ```

2. **Mercure broadcasts**: Mercure receives updates and broadcasts them to all subscribers

3. **Frontend subscribes**: Browser connects via EventSource (SSE)
   ```javascript
   const eventSource = new EventSource('http://localhost:3000/.well-known/mercure?topic=benchmark/progress');
   ```

4. **Live updates**: Frontend receives real-time updates and updates UI automatically

**Configuration**: See [mercure-realtime.md](mercure-realtime.md) for detailed setup

**Topics**:
- `benchmark/progress` - All progress updates (start, progress, complete)
- `benchmark/results` - Final results only

**Testing Mercure**:

```bash
# Subscribe to updates (terminal 1)
curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress"

# Run benchmark (terminal 2)
make run test=Loop iterations=5

# Terminal 1 will show real-time SSE events
```

**Security note**: Current configuration uses `anonymous` mode for development. For production, implement proper JWT authentication. See [mercure-realtime.md#security](mercure-realtime.md#security).

---

## Best Practices

### Development

1. **Hot-reload**: Code changes on host are immediately available in containers (bind mount)
2. **Dependency updates**: Run `composer install` inside main container:
   ```bash
   docker-compose exec main composer install
   ```
3. **Database migrations**: Execute inside main container:
   ```bash
   docker-compose exec main php bin/console doctrine:migrations:migrate
   ```

### Production Considerations

**Current setup is development-oriented**. For production, consider:

- ❌ Don't expose MariaDB port `3306` publicly
- ❌ Don't use `root` password in environment variables
- ❌ Don't use `tail -f /dev/null` (use proper orchestration)
- ✅ Use Docker secrets for credentials
- ✅ Use environment-specific `docker-compose.override.yml`
- ✅ Enable Docker resource monitoring
- ✅ Implement health checks

### Security

**Docker socket access**: The main container has access to `/var/run/docker.sock`, which grants root-level control over Docker. This is acceptable for development but risky in production.

**Mitigation**:
- Use Docker API with restricted permissions
- Consider rootless Docker
- Isolate benchmark containers with stricter security policies

---

## Troubleshooting

### Container won't start

```bash
# Check logs
docker-compose logs <service_name>

# Example: Check MariaDB logs
docker-compose logs mariadb
```

### Database connection failed

**Symptom**: `Connection refused` or `Unknown MySQL server host`

**Solution**:

```bash
# Ensure MariaDB is running
docker-compose ps mariadb

# Test connection from main container
docker-compose exec main mysql -h mariadb -u root -ppassword php_benchmark
```

### Benchmark execution timeout

**Symptom**: `Script execution failed with code 124`

**Cause**: Benchmark took longer than allowed timeout

**Solution**: Increase timeout in `DockerScriptExecutor` or reduce iterations

### Out of memory errors

**Symptom**: Benchmark container crashes with exit code 137

**Cause**: Exceeded 512MB memory limit

**Solution**: Increase `mem_limit` in `docker-compose.yml` (note: may affect benchmark fairness)

---

## Future Improvements

### Proposed Enhancements

1. **Health checks**: Add Docker health checks for all services
   ```yaml
   healthcheck:
     test: ["CMD", "php", "-v"]
     interval: 30s
     timeout: 3s
     retries: 3
   ```

2. **Multi-stage builds**: Reduce image size for PHP 8.5
   ```dockerfile
   FROM debian:bullseye AS builder
   # ... compile PHP ...

   FROM debian:bullseye-slim
   COPY --from=builder /usr/local/php8.5 /usr/local/php8.5
   ```

3. **Container orchestration**: Consider Kubernetes for production scaling

4. **Monitoring**: Add Prometheus exporters for container metrics

5. **Caching**: Use Docker build cache optimization for faster rebuilds

---

## References

- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [PHP Official Docker Images](https://hub.docker.com/_/php)
- [MariaDB Docker Image](https://hub.docker.com/_/mariadb)
- [Mercure Docker Image](https://hub.docker.com/r/dunglas/mercure)
- [DockerScriptExecutor Implementation](../../src/Infrastructure/Execution/Docker/DockerScriptExecutor.php)
- [Real-Time Updates Guide](mercure-realtime.md)

---

**Last updated**: 2025-10-22
**Maintained by**: Project contributors
