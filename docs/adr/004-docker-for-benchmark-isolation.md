# ADR-004: Use Docker for Benchmark Execution Isolation

**Status**: Accepted  
**Date**: 2024-08-26  
**Deciders**: Development Team

## Context

The application compares PHP performance across:
- **Multiple PHP versions** (8.0, 8.1, 8.2, 8.3, 8.4, 8.5)
- **Different methods** (e.g., `for` vs `foreach`, `array_map` vs manual loops)

### Challenges

1. **Version isolation**: Need to run benchmarks on different PHP versions without conflicts
2. **Consistent environment**: CPU, memory, and OS must be identical for fair comparison
3. **Reproducibility**: Same benchmark must produce consistent results across runs
4. **Security**: User-provided code (YAML fixtures) must run in isolated environment
5. **Scalability**: Must support concurrent benchmark execution

### Options Considered

1. **Native execution**: Run benchmarks directly on host PHP
2. **PHP version managers** (phpenv, phpbrew): Switch PHP versions
3. **Virtual machines**: Separate VM per PHP version
4. **Docker containers**: Containerized PHP environments

## Decision

We use **Docker containers** with dedicated images per PHP version because:

1. **Complete isolation**: Each benchmark runs in separate container with own filesystem, network, processes
2. **Version flexibility**: Easy to add new PHP versions by creating new Dockerfile
3. **Reproducible environment**: Container images ensure identical environment every run
4. **Resource control**: Can limit CPU/memory per container to prevent resource exhaustion
5. **Security**: Containers provide process-level isolation from host system
6. **Developer experience**: Same environment for development and production

### Architecture

```
Host (Symfony App)
    ↓ executes via DockerBenchmarkExecutor
Docker Engine
    ↓ spawns containers
    ├─ php84 container (FROM php:8.4-cli)
    ├─ php85 container (FROM php:8.5-cli)
    └─ php83 container (FROM php:8.3-cli)
```

### Implementation

**Dockerfiles** (one per version):
```dockerfile
# Dockerfile.php84
FROM php:8.4-cli
WORKDIR /app
RUN apt-get update && apt-get install -y git unzip
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
```

**Execution** (`DockerBenchmarkExecutor`):
```php
$command = sprintf(
    'docker run --rm -v %s:/app php84 php /app/benchmark.php',
    $benchmarkDir
);
```

**Docker Compose** (multi-version setup):
```yaml
services:
  php84:
    build:
      dockerfile: Dockerfile.php84
    volumes:
      - ./:/var/www/php_benchmark
      
  php85:
    build:
      dockerfile: Dockerfile.php85
    volumes:
      - ./:/var/www/php_benchmark
```

## Consequences

### Positive
- **Version independence**: Run PHP 8.0 and 8.5 benchmarks simultaneously
- **Fair comparison**: All benchmarks run in identical environments (same base image)
- **Easy updates**: Update PHP version by changing Dockerfile base image
- **CI/CD ready**: Same Docker setup works in development and CI pipeline
- **Security**: Malicious code contained within container
- **Debugging**: Can inspect container filesystem after execution

### Negative
- **Overhead**: Container startup adds ~100-200ms per benchmark execution
- **Disk space**: Each PHP version image requires ~400-500MB
- **Complexity**: Requires Docker daemon running, docker-compose configuration
- **Resource usage**: Running multiple containers consumes more memory than native execution

### Trade-offs Accepted
- We accept startup overhead for isolation and reproducibility
- We accept disk space cost for version flexibility
- We accept operational complexity for security and consistency
- Container overhead is negligible compared to benchmark execution time (seconds)

## Alternatives Not Chosen

### Native Execution
```bash
php /path/to/benchmark.php
```
**Rejected**: Cannot switch PHP versions, no isolation, security risk

### PHP Version Managers (phpenv)
```bash
phpenv local 8.4.0
php benchmark.php
```
**Rejected**: Difficult to manage multiple versions, no process isolation, not CI-friendly

### Virtual Machines
**Rejected**: Too heavy (GB of disk space), slow startup (minutes), complex setup

## Performance Considerations

Benchmark execution time breakdown:
- Container startup: ~150ms
- PHP JIT warmup: ~50ms  
- Actual benchmark: 1-10 seconds (1000 iterations)

Container overhead is **<2%** of total execution time, acceptable for isolation benefits.

## Future Improvements

1. **Image caching**: Pre-pull all PHP images to reduce first-run latency
2. **Resource limits**: Add `--cpus` and `--memory` flags for consistent resource allocation
3. **Parallel execution**: Run multiple benchmarks concurrently using container pools
4. **ARM support**: Add multi-arch images for ARM-based systems (Apple Silicon)

## References
- [Docker Documentation](https://docs.docker.com/)
- [PHP Official Docker Images](https://hub.docker.com/_/php)
- Project implementation: `src/Infrastructure/Execution/DockerBenchmarkExecutor.php`
- Build configuration: `Dockerfile.main`, `Dockerfile.php85`, `docker-compose.yml`
