# Quick Reference for Coding Agents

## Commands
```bash
# Database & Fixtures
make db.reset          # Drop, create, migrate database (empty)
make db.refresh        # Reset database + load YAML fixtures
make fixtures          # Load benchmarks from fixtures/benchmarks/*.yaml

# Tests
docker-compose run --rm main vendor/bin/phpunit                    # All tests
docker-compose run --rm main vendor/bin/phpunit tests/Path/To/SpecificTest.php  # Single test

# Code Quality
make phpcsfixer-fix    # Fix code style (PSR-12)
make phpstan           # Static analysis (level 9)
make quality           # Run all checks + fixes

# Assets (CSS/JS)
make assets.refresh    # Force refresh assets: compile, regenerate hashes, clear cache, restart
                       # Use when CSS/JS changes don't appear in browser
                       # Don't forget to hard refresh browser (Ctrl+Shift+R)

# Benchmarks
make run test=Loop iterations=10              # Run specific benchmark
docker-compose run --rm main php bin/console benchmark:run --test=Loop --php-version=php84

# Benchmark Calibration
docker-compose run --rm main php bin/console benchmark:calibrate --all --dry-run  # Preview optimal iterations
docker-compose run --rm main php bin/console benchmark:calibrate --all            # Apply calibration
docker-compose run --rm main php bin/console benchmark:calibrate --benchmark=access-instance-property  # Single benchmark

# Mercure (Real-Time)
./scripts/mercure-verify.sh              # Verify Mercure setup
./scripts/mercure-listen.sh              # Watch real-time events
./scripts/mercure-test.sh 5 Loop php84   # End-to-end test
```

## Code Style
- **PHP 8.4+** with `declare(strict_types=1)` at top of every file
- **PSR-12** + Symfony style enforced by PHP-CS-Fixer
- **Imports**: Fully qualified, alphabetically sorted (classes, functions, constants)
- **Arrays**: Short syntax `[]`, trailing commas in multiline
- **Strings**: Concatenation with ` . ` (spaces around dot)
- **Comparison**: Strict (`===`, `!==`) always, Yoda style (`null === $var`)
- **Classes**: `final readonly` by default, ordered elements (constants → properties → constructor → methods)
- **Types**: Full type hints everywhere (params, returns, properties with asymmetric visibility `public private(set)`)
- **PHPDoc**: Only for interfaces, complex return types (`@return Type[]`), or "why" explanations

## Architecture (Clean + DDD + Hexagonal)
- **Namespace**: `Jblairy\PhpBenchmark\{Domain|Application|Infrastructure}\...`
- **Dependencies flow INWARD**: Infrastructure → Application → Domain
- **Domain**: Pure PHP, no framework (Symfony/Doctrine), defines Ports (interfaces)
- **Infrastructure**: Implements Adapters for Domain Ports, uses any library
- **Naming**: Ports end with `Port`, Value Objects are nouns, Services are verbs

See [docs/architecture/01-overview.md](docs/architecture/01-overview.md) and [CLAUDE.md](CLAUDE.md) for details.
