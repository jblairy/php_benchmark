# PHP Benchmark Suite

A modern, benchmarking framework for PHP that allows testing performance of different implementations and evaluating performance evolution across PHP versions (5.6 through 8.5).

## Features

- **Performance Testing**: 40+ automated benchmarks covering arrays, strings, loops, OOP, and more
- **Version Comparison**: Test performance across different PHP versions (5.6 to 8.5)
- **Parallel Execution**: Concurrent benchmark execution using Spatie\Async (100 parallel tasks)
- **Docker Isolation**: Each PHP version runs in isolated Docker containers
- **Web Dashboard**: Visual charts and statistics at `/dashboard`
- **Clean Architecture**: Domain-driven design with hexagonal ports & adapters
- **Modular Architecture**: Easily add your own test cases

## Requirements

- docker
- docker-compose

## Installation

```bash
git clone https://github.com/jblairy/php-benchmark.git
cd php-benchmark
make up
```

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

This project implements **Clean Architecture + DDD + Hexagonal Architecture**.

```
src/
├── Application/        # Use Cases (orchestration)
├── Domain/            # Business Logic (pure PHP, no framework)
│   └── Benchmark/
│       ├── Model/      # Value Objects (immutable)
│       ├── Port/       # Interfaces (Hexagonal Ports)
│       ├── Service/    # Domain Services
│       └── Test/       # 40+ benchmark implementations
└── Infrastructure/    # Technical implementations (adapters)
    ├── Cli/           # Symfony Console commands
    ├── Execution/     # Docker, code extraction
    ├── Persistence/   # Doctrine ORM, repositories
    └── Web/          # Dashboard controllers
```

**Key Principle:** Dependencies point inward → Infrastructure → Application → Domain

### Documentation

- **[docs/README.md](docs/README.md)** - Complete documentation index
- **[docs/architecture/01-overview.md](docs/architecture/01-overview.md)** - Architecture deep dive
- **[CLAUDE.md](CLAUDE.md)** - Developer reference guide

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

1. **Create a class in `src/Domain/Benchmark/Test/`**
2. **Extend `AbstractBenchmark`**
3. **Add PHP version attributes**
4. **Implement your test method**

**Example:**

```php
<?php

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Test;

use Jblairy\PhpBenchmark\Domain\Benchmark\Contract\AbstractBenchmark;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\All;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Attribute\Php80; // PHP 8.0+

final class MyCustomBenchmark extends AbstractBenchmark
{
    #[All]
    public function withForLoop(): void
    {
        $result = [];
        for ($i = 0; $i < 10000; $i++) {
            $result[] = $i * 2;
        }
    }

    #[Php80] // Only on PHP 8.0+
    public function withMatchExpression(): void
    {
        $result = match (true) {
            true => 'success',
            false => 'failure',
        };
    }
}
```

**Available Attributes:**
- `#[All]` - Run on all PHP versions
- `#[Php56]`, `#[Php70]`, `#[Php71]`, `#[Php72]`, `#[Php73]`, `#[Php74]` - Legacy versions
- `#[Php80]`, `#[Php81]`, `#[Php82]`, `#[Php83]`, `#[Php84]`, `#[Php85]` - Modern versions

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

