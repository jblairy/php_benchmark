<div align="center">

# 🚀 PHP Benchmark Suite

[![PHP Version](https://img.shields.io/badge/PHP-8.4+-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%20max-brightgreen?style=flat-square)](https://phpstan.org/)
[![Code Style](https://img.shields.io/badge/code%20style-PSR--12-blue?style=flat-square)](https://www.php-fig.org/psr/psr-12/)
[![Tests](https://img.shields.io/badge/tests-50%20passed-success?style=flat-square)](https://phpunit.de/)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)

**A modern benchmarking framework for PHP testing performance across versions 5.6 to 8.5**

[Features](#-features) • [Installation](#-installation) • [Usage](#-usage) • [Architecture](#-architecture) • [Contributing](#-contributing)

</div>

---

## ✨ Features

<table>
  <tr>
    <td width="50%">
      <h3>🎯 Performance Testing</h3>
      <ul>
        <li><strong>107+ Benchmarks</strong> covering arrays, strings, loops, OOP, functions, and more</li>
        <li><strong>Statistical Analysis</strong> with percentiles (p50, p80, p90, p95, p99)</li>
        <li><strong>Memory Profiling</strong> tracks memory usage and peaks</li>
      </ul>
    </td>
    <td width="50%">
      <h3>🔄 Version Comparison</h3>
      <ul>
        <li><strong>Multi-Version Testing</strong> from PHP 5.6 to 8.5</li>
        <li><strong>Visual Charts</strong> compare performance across versions</li>
        <li><strong>Evolution Tracking</strong> see how PHP improves over time</li>
      </ul>
    </td>
  </tr>
  <tr>
    <td width="50%">
      <h3>⚡ High Performance</h3>
      <ul>
        <li><strong>Parallel Execution</strong> using Spatie\Async (100 concurrent tasks)</li>
        <li><strong>Docker Isolation</strong> each version in isolated containers</li>
        <li><strong>Real-Time Updates</strong> via Mercure (Server-Sent Events)</li>
      </ul>
    </td>
    <td width="50%">
      <h3>🏗️ Modern Architecture</h3>
      <ul>
        <li><strong>Clean Architecture</strong> with DDD + Hexagonal design</li>
        <li><strong>SOLID Principles</strong> validated by PHPStan level max</li>
        <li><strong>Architecture Tests</strong> enforced by PHPArkitect</li>
      </ul>
    </td>
  </tr>
  <tr>
    <td width="50%">
      <h3>📊 Web Dashboard</h3>
      <ul>
        <li><strong>Interactive Charts</strong> powered by Chart.js</li>
        <li><strong>Live Progress</strong> watch benchmarks execute in real-time</li>
        <li><strong>Detailed Stats</strong> view execution times, memory, percentiles</li>
      </ul>
    </td>
    <td width="50%">
      <h3>🎨 Easy Customization</h3>
      <ul>
        <li><strong>YAML Fixtures</strong> define benchmarks in simple YAML files</li>
        <li><strong>Database Storage</strong> benchmarks persisted in MariaDB</li>
        <li><strong>Hot Reload</strong> add new benchmarks without code changes</li>
      </ul>
    </td>
  </tr>
</table>

## 📋 Requirements

| Requirement | Version | Purpose |
|------------|---------|---------|
| Docker | Latest | Container runtime |
| Docker Compose | v2+ | Multi-container orchestration |
| Make | Any | Task automation (optional) |

## 🚀 Installation

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

## 💻 Usage

### Quick Start

```bash
# Run all benchmarks
make run

# View results in browser
open http://localhost/dashboard
```

### Advanced Usage

<details>
<summary><strong>Run Specific Benchmark</strong></summary>

```bash
# Run a specific test
make run test=Loop

# Run with custom iterations
make run test=Loop iterations=100

# Run on specific PHP version
docker-compose run --rm main php bin/console benchmark:run \
  --test=Loop \
  --php-version=php84 \
  --iterations=10
```
</details>

<details>
<summary><strong>Development Commands</strong></summary>

```bash
# Database management
make db.reset          # Drop and recreate database (empty)
make db.refresh        # Reset database + load fixtures
make fixtures          # Load benchmark fixtures only

# Testing
make test              # Run all PHPUnit tests (50 tests, 212 assertions)
make test-coverage     # Run tests with coverage report

# Code Quality
make phpstan           # Static analysis (level max)
make phpcsfixer        # Check code style (PSR-12)
make phpcsfixer-fix    # Fix code style automatically
make quality           # Run all quality checks

# Assets (CSS/JS)
make assets.refresh    # Rebuild and refresh frontend assets
```
</details>

<details>
<summary><strong>Mercure Real-Time Testing</strong></summary>

```bash
# Verify Mercure setup
./scripts/mercure-verify.sh

# Watch real-time events
./scripts/mercure-listen.sh

# End-to-end test (5 iterations, Loop benchmark, PHP 8.4)
./scripts/mercure-test.sh 5 Loop php84
```
</details>

### 📊 View Results

Access the web dashboard to explore benchmark results:
- **URL**: `http://localhost/dashboard`
- **Features**: Interactive charts, live progress tracking, detailed statistics

## 🏗️ Architecture

This project follows **Clean Architecture** principles with **Domain-Driven Design (DDD)** and **Hexagonal Architecture** (Ports & Adapters).

```
┌─────────────────────────────────────────────────────────┐
│                    Infrastructure                        │
│  (Symfony, Doctrine, Docker, Web, CLI, Persistence)    │
│                           ↓                              │
│                      Application                         │
│              (Use Cases, Orchestration)                  │
│                           ↓                              │
│                        Domain                            │
│          (Business Logic - Pure PHP)                     │
└─────────────────────────────────────────────────────────┘
```

### Directory Structure

```
src/
├── Domain/                     # 🎯 Core Business Logic (Framework-free)
│   ├── Benchmark/             # Benchmark models, value objects, ports
│   ├── Dashboard/             # Dashboard domain models, statistics
│   └── PhpVersion/            # PHP version enumeration
│
├── Application/               # 📦 Use Cases (Orchestration Layer)
│   ├── UseCase/               # Benchmark execution orchestration
│   └── Dashboard/             # Dashboard data aggregation
│
└── Infrastructure/            # 🔧 Technical Implementation
    ├── Async/                 # Async execution (Spatie\Async)
    ├── Cli/                   # Symfony Console commands
    ├── Execution/             # Docker script execution
    ├── Mercure/               # Real-time SSE events
    ├── Persistence/           # Doctrine entities, repositories, fixtures
    └── Web/                   # Symfony controllers, components, Twig
```

### Key Principles

- ✅ **Dependencies flow inward**: Infrastructure → Application → Domain
- ✅ **Domain is framework-agnostic**: Pure PHP, no Symfony/Doctrine dependencies
- ✅ **Ports & Adapters**: Domain defines interfaces (Ports), Infrastructure implements them (Adapters)
- ✅ **Validated by PHPArkitect**: Architecture rules are enforced automatically

### 📚 Documentation

| Document | Description |
|----------|-------------|
| [docs/architecture/01-overview.md](docs/architecture/01-overview.md) | Architecture deep dive |
| [docs/architecture/02-layers.md](docs/architecture/02-layers.md) | Layer responsibilities |
| [docs/architecture/03-ports-adapters.md](docs/architecture/03-ports-adapters.md) | Hexagonal architecture details |
| [CLAUDE.md](CLAUDE.md) | Developer reference guide |
| [docs/README.md](docs/README.md) | Complete documentation index |

## 🤝 Contributing

We love contributions! Whether it's bug fixes, new benchmarks, or documentation improvements, all contributions are welcome.

### Contribution Guidelines

<table>
  <tr>
    <td>📝</td>
    <td><strong>Code Style</strong></td>
    <td>Follow <strong>PSR-12</strong> standards (enforced by PHP-CS-Fixer)</td>
  </tr>
  <tr>
    <td>🔍</td>
    <td><strong>Static Analysis</strong></td>
    <td>Pass <strong>PHPStan level max</strong> (strictest level)</td>
  </tr>
  <tr>
    <td>🏗️</td>
    <td><strong>Architecture</strong></td>
    <td>Respect <strong>Clean Architecture</strong> principles (validated by PHPArkitect)</td>
  </tr>
  <tr>
    <td>✅</td>
    <td><strong>Testing</strong></td>
    <td>Write <strong>PHPUnit tests</strong> for new features</td>
  </tr>
  <tr>
    <td>🌐</td>
    <td><strong>Language</strong></td>
    <td>Write all code, comments, docs, commits, PRs in <strong>English</strong></td>
  </tr>
  <tr>
    <td>📚</td>
    <td><strong>Documentation</strong></td>
    <td>Document your benchmarks and architectural decisions</td>
  </tr>
</table>

### Development Workflow

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Make** your changes
4. **Run quality checks**: `make quality`
5. **Run tests**: `make test`
6. **Commit** your changes (see [Atomic Commits Guide](docs/guides/atomic-commits.md))
7. **Push** to your branch (`git push origin feature/amazing-feature`)
8. **Open** a Pull Request

### Quick Quality Check

```bash
# Run all quality checks before committing
make quality

# This runs:
# ✓ PHPStan (static analysis)
# ✓ PHP-CS-Fixer (code style)
# ✓ PHPUnit (tests)
# ✓ PHPMD (mess detection)
# ✓ PHPArkitect (architecture validation)
```

### 📖 Developer Resources

- **[CLAUDE.md](CLAUDE.md)** - Complete developer reference guide
- **[docs/guides/creating-benchmarks.md](docs/guides/creating-benchmarks.md)** - How to add benchmarks
- **[docs/guides/atomic-commits.md](docs/guides/atomic-commits.md)** - Commit best practices
- **[docs/architecture/](docs/architecture/)** - Architecture documentation

### 🎯 Creating Custom Benchmarks

Benchmarks are defined as **YAML files** in `fixtures/benchmarks/`. No PHP code changes needed!

<details>
<summary><strong>Click to see example benchmark</strong></summary>

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
make fixtures   # Load fixtures only
# or
make db.refresh # Reset database + reload all benchmarks
```
</details>

📖 **Full guide**: [docs/guides/creating-benchmarks.md](docs/guides/creating-benchmarks.md)

## 🛠️ Tech Stack

<table>
  <tr>
    <td align="center" width="150">
      <img src="https://www.php.net/images/logos/new-php-logo.svg" width="48" height="48" alt="PHP"/><br/>
      <strong>PHP 8.4+</strong>
    </td>
    <td align="center" width="150">
      <img src="https://symfony.com/logos/symfony_black_02.svg" width="48" height="48" alt="Symfony"/><br/>
      <strong>Symfony 7.2</strong>
    </td>
    <td align="center" width="150">
      <img src="https://www.doctrine-project.org/logos/doctrine-logo.svg" width="48" height="48" alt="Doctrine"/><br/>
      <strong>Doctrine ORM</strong>
    </td>
    <td align="center" width="150">
      <img src="https://www.docker.com/wp-content/uploads/2022/03/vertical-logo-monochromatic.png" width="48" height="48" alt="Docker"/><br/>
      <strong>Docker</strong>
    </td>
  </tr>
  <tr>
    <td align="center">
      <img src="https://mariadb.com/wp-content/uploads/2019/11/mariadb-logo-vert_blue-transparent.png" width="48" height="48" alt="MariaDB"/><br/>
      <strong>MariaDB</strong>
    </td>
    <td align="center">
      <img src="https://mercure.rocks/static/logo-blue.svg" width="48" height="48" alt="Mercure"/><br/>
      <strong>Mercure</strong>
    </td>
    <td align="center">
      <img src="https://www.chartjs.org/media/logo-title.svg" width="48" height="48" alt="Chart.js"/><br/>
      <strong>Chart.js</strong>
    </td>
    <td align="center">
      <img src="https://stimulus.hotwired.dev/assets/stimulus-logo.svg" width="48" height="48" alt="Stimulus"/><br/>
      <strong>Stimulus</strong>
    </td>
  </tr>
</table>

### Quality Tools

| Tool | Purpose | Level/Standard |
|------|---------|----------------|
| **PHPStan** | Static analysis | Level max (strictest) |
| **PHP-CS-Fixer** | Code style | PSR-12 + Symfony |
| **PHPUnit** | Unit testing | 50 tests, 212 assertions |
| **PHPArkitect** | Architecture validation | Clean Architecture rules |
| **PHPMD** | Mess detection | Custom ruleset |
| **Infection** | Mutation testing | Available |

## 📊 Project Stats

- **🎯 Benchmarks**: 107 automated tests
- **✅ Tests**: 50 passing (212 assertions)
- **📝 Code Quality**: PHPStan level max, PSR-12 compliant
- **🏗️ Architecture**: Clean Architecture + DDD + Hexagonal
- **🔄 PHP Versions**: Supports 5.6 to 8.5
- **📚 Documentation**: 15+ detailed guides and ADRs

## 📄 License

This project is licensed under the **MIT License**. See the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **PHP Community** - For continuous inspiration and improvements
- **Contributors** - Thank you to everyone who has contributed to this project
- **Open Source Projects** - Built on the shoulders of giants

### Key Dependencies

Special thanks to the maintainers of:
- [Symfony](https://symfony.com/) - The PHP framework
- [Doctrine](https://www.doctrine-project.org/) - Database ORM
- [Spatie Async](https://github.com/spatie/async) - Parallel execution
- [Mercure](https://mercure.rocks/) - Real-time updates
- [PHPStan](https://phpstan.org/) - Static analysis

## 📞 Support & Community

<table>
  <tr>
    <td>🐛 <strong>Issues</strong></td>
    <td><a href="https://github.com/jblairy/php_benchmark/issues">Report bugs or request features</a></td>
  </tr>
  <tr>
    <td>💬 <strong>Discussions</strong></td>
    <td><a href="https://github.com/jblairy/php_benchmark/discussions">Ask questions and share ideas</a></td>
  </tr>
  <tr>
    <td>📧 <strong>Contact</strong></td>
    <td>Reach out via GitHub issues or discussions</td>
  </tr>
</table>

## 🌟 Show Your Support

If this project helped you, please consider:

- ⭐ **Star this repository** on GitHub
- 🔀 **Fork** and contribute
- 📢 **Share** with the PHP community
- 💡 **Open issues** for improvements

---

<div align="center">

**Made with ❤️ for the PHP Community**

[![GitHub stars](https://img.shields.io/github/stars/jblairy/php_benchmark?style=social)](https://github.com/jblairy/php_benchmark)
[![GitHub forks](https://img.shields.io/github/forks/jblairy/php_benchmark?style=social)](https://github.com/jblairy/php_benchmark/fork)
[![GitHub watchers](https://img.shields.io/github/watchers/jblairy/php_benchmark?style=social)](https://github.com/jblairy/php_benchmark)

</div>

