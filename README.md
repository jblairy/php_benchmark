# PHP Benchmark Suite

A modern, benchmarking framework for PHP that allows testing performance of different implementations and evaluating performance evolution across PHP versions (5.6 through 8.5).

## Features

- **Performance Testing**: 100+ automated benchmarks covering arrays, strings, loops, OOP, and more
- **Version Comparison**: Test performance across different PHP versions (5.6 to 8.5)
- **Parallel Execution**: Concurrent benchmark execution using Spatie\Async (100 parallel tasks)
- **Docker Isolation**: Each PHP version runs in isolated Docker containers
- **Web Dashboard**: Visual charts and statistics at `/dashboard` with real-time updates
- **Real-Time Progress**: Live benchmark progress using Mercure (Server-Sent Events)
- **Database Persistence**: Benchmark definitions stored in MariaDB via YAML fixtures
- **Clean Architecture**: Domain-driven design with hexagonal ports & adapters
- **Fixture-Based**: Easily add benchmarks via YAML files

## Requirements

- docker
- docker-compose

## Installation

```bash
git clone https://github.com/jblairy/php-benchmark.git
cd php-benchmark
make up              # Start Docker containers
make db.refresh      # Create database and load benchmark fixtures
```

The `make db.refresh` command will:
1. Create the MariaDB database
2. Run migrations to create tables
3. Load 100+ benchmark definitions from YAML fixtures

## Usage

### Run All Benchmarks
```bash
make run
```

### Run Specific Benchmark
```bash
# Run a specific test
make run test=Loop

# Run with specific iterations
make run test=Loop iterations=100

# Run on specific PHP version
docker-compose run --rm main php bin/console benchmark:run --test=Loop --php-version=php84 --iterations=10
```

### View Results
Open your browser at `http://localhost/dashboard` to see charts and statistics.

## Architecture

**Clean Architecture + DDD + Hexagonal** (Ports & Adapters).

```
src/
├── Domain/            # Business logic (pure PHP, no framework)
├── Application/       # Use cases (orchestration)
└── Infrastructure/    # Technical details (Symfony, Doctrine, Docker)
```

**Dependencies flow inward**: Infrastructure → Application → Domain

📖 **Full documentation**:
- [docs/architecture/01-overview.md](docs/architecture/01-overview.md) - Architecture deep dive
- [CLAUDE.md](CLAUDE.md) - Developer reference guide
- [docs/README.md](docs/README.md) - Complete documentation index

## Contributing

Contributions are welcome! Here's how to contribute:

### Contribution Guidelines
- **Write all documentation in English** (code, comments, docs, commits, PRs)
- Follow **PSR-12** coding standards (enforced by PHP-CS-Fixer)
- Follow **PHPStan level 9** rules
- Respect **Clean Architecture** principles (validated by PHPArkitect)
- Document your benchmarks
- Write tests for new features

See **[CLAUDE.md](CLAUDE.md)** for detailed developer guidelines.

### Creating Custom Benchmarks

Benchmarks are now defined as YAML files in `fixtures/benchmarks/`:

```yaml
# fixtures/benchmarks/my-benchmark.yaml
slug: my-benchmark
name: 'My Custom Benchmark'
category: 'Custom'
description: 'Description of what this benchmark tests'
icon: 🚀
tags:
  - custom
  - performance
phpVersions:
  - php84
  - php85
code: |
  // Your benchmark code here
  $result = [];
  for ($i = 0; $i < 10000; $i++) {
      $result[] = $i * 2;
  }
```

**Load the new benchmark:**
```bash
make fixtures   # or make db.refresh to reload all
```

📖 **Full guide**: [docs/guides/creating-benchmarks.md](docs/guides/creating-benchmarks.md)

### Code Quality Tools

```bash
# Check code style
make phpcsfixer

# Fix code style
make phpcsfixer-fix

# Run static analysis
make phpstan

# Run architecture validation
docker-compose run --rm main vendor/bin/phparkitect check

# Run all quality checks
make quality
```


## License
This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Acknowledgments
- PHP Community for inspiration
- Project contributors
- Everyone who tests and reports issues

## Support
- **Issues**: [GitHub Issues](https://github.com/jblairy/php-benchmark/issues)
- **Discussions**: [GitHub Discussions](https://github.com/jblairy/php-benchmark/discussions)

⭐ If you like this project, please give it a star!

