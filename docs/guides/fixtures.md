# Benchmark Fixtures Guide

**Status**: Active  
**Last Updated**: 2025-11-03

## Overview

Benchmarks are now defined as **YAML files** in `fixtures/benchmarks/` and loaded into the MariaDB database. This approach provides:

- ✅ **Version control**: Benchmarks are tracked in Git
- ✅ **Easy editing**: No PHP code needed to create benchmarks
- ✅ **Database persistence**: Fast queries and relations
- ✅ **Separation of concerns**: Code definitions separate from execution logic
- ✅ **Bulk management**: Load 100+ benchmarks at once

## Architecture

```
fixtures/benchmarks/*.yaml  →  YamlBenchmarkFixtures  →  Benchmark Entity  →  Database
     (Source)                    (Doctrine Loader)         (Doctrine ORM)      (MariaDB)
```

### Components

| Component | Location | Purpose |
|-----------|----------|---------|
| **YAML Files** | `fixtures/benchmarks/*.yaml` | Source of truth for benchmark definitions |
| **Fixture Loader** | `src/Infrastructure/Persistence/Doctrine/Fixtures/YamlBenchmarkFixtures.php` | Parses YAML and creates entities |
| **Benchmark Entity** | `src/Infrastructure/Persistence/Doctrine/Entity/Benchmark.php` | Doctrine ORM entity |
| **Database Table** | `benchmarks` | MariaDB table (migration `Version20251103175019`) |

## YAML File Format

### Required Fields

```yaml
slug: unique-benchmark-name       # Unique identifier (used in URLs)
name: 'Human Readable Name'       # Display name
category: 'Category Name'         # Group benchmarks (e.g., "Array Operations")
code: |                           # PHP code to execute (multiline)
  // Your benchmark code here
  $result = [];
  for ($i = 0; $i < 1000; $i++) {
      $result[] = $i;
  }
phpVersions:                      # Array of PHP versions to test
  - php56
  - php84
  - php85
```

### Optional Fields

```yaml
description: 'Detailed explanation'  # Optional description (empty string if omitted)
icon: 🚀                             # Optional emoji icon (null if omitted)
tags:                                # Optional array of tags (empty array if omitted)
  - array
  - performance
  - loop
```

## Complete Example

**File**: `fixtures/benchmarks/array-fill-benchmark.yaml`

```yaml
slug: array-fill
name: 'Array Fill'
category: 'Array Operations'
description: 'Compare performance of array_fill() vs manual loop for array initialization'
icon: 📦
tags:
  - array
  - fill
  - initialization
phpVersions:
  - php56
  - php70
  - php71
  - php72
  - php73
  - php74
  - php80
  - php81
  - php82
  - php83
  - php84
  - php85
code: |
  // Benchmark: array_fill() performance
  for ($i = 0; $i < 10000; $i++) {
      $array = array_fill(0, 100, 'value');
  }
```

## Available PHP Versions

Valid values for `phpVersions` array:

```yaml
phpVersions:
  - php56   # PHP 5.6
  - php70   # PHP 7.0
  - php71   # PHP 7.1
  - php72   # PHP 7.2
  - php73   # PHP 7.3
  - php74   # PHP 7.4
  - php80   # PHP 8.0
  - php81   # PHP 8.1
  - php82   # PHP 8.2
  - php83   # PHP 8.3
  - php84   # PHP 8.4
  - php85   # PHP 8.5 (alpha)
```

**Note**: These values must match the `PhpVersion` enum in `src/Domain/PhpVersion/Enum/PhpVersion.php`.

## Loading Fixtures

### Commands

```bash
# Load fixtures (append to existing)
make fixtures
# or
docker-compose run --rm main php bin/console doctrine:fixtures:load --no-interaction

# Reset database and load fixtures (recommended)
make db.refresh
```

### What Happens

1. **Scan**: `YamlBenchmarkFixtures` scans `fixtures/benchmarks/*.yaml`
2. **Parse**: Each YAML file is parsed using Symfony YAML component
3. **Validate**: Required fields are validated
4. **Create**: Doctrine `Benchmark` entity is created
5. **Persist**: Entity is persisted to `benchmarks` table
6. **Errors**: Invalid files are logged but don't stop the process

### Error Handling

If a YAML file is invalid:
- ❌ Error is logged: `Failed to load benchmark fixture {filename}: {error}`
- ✅ Other files continue loading
- ✅ Process completes successfully

Check logs: `docker-compose logs main`

## Creating New Benchmarks

### Step 1: Create YAML File

```bash
# Create file in fixtures/benchmarks/
touch fixtures/benchmarks/my-benchmark.yaml
```

### Step 2: Define Benchmark

```yaml
slug: my-benchmark
name: 'My Custom Benchmark'
category: 'Custom Tests'
description: 'What this benchmark measures'
icon: 🚀
tags:
  - custom
phpVersions:
  - php84
  - php85
code: |
  // Your benchmark code
  $result = 0;
  for ($i = 0; $i < 1000; $i++) {
      $result += $i;
  }
```

### Step 3: Load Fixtures

```bash
make fixtures
```

### Step 4: Verify

```bash
# Check database
docker-compose exec database mariadb -u root -p -e "SELECT slug, name FROM benchmarks WHERE slug='my-benchmark';"

# Or visit dashboard
# http://localhost/dashboard
```

## Validation Rules

### Slug Rules

- ✅ Must be unique across all benchmarks
- ✅ Lowercase with hyphens (e.g., `array-fill-benchmark`)
- ✅ No spaces, no special characters
- ✅ Used in URLs and identifiers

### Code Rules

- ✅ Must not be empty
- ✅ Can use any valid PHP code
- ✅ Executed in isolated Docker containers
- ✅ No access to application code/database

### PHP Version Rules

- ✅ Must be non-empty array
- ✅ Must use valid enum values (`php56`, `php84`, etc.)
- ❌ Invalid values will cause errors

## Fixture Loader Implementation

```php
// src/Infrastructure/Persistence/Doctrine/Fixtures/YamlBenchmarkFixtures.php

class YamlBenchmarkFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 1. Find all YAML files
        $finder = new Finder();
        $finder->files()
            ->in($this->projectDir . '/fixtures/benchmarks')
            ->name('*.yaml')
            ->sortByName();

        // 2. Parse each file
        foreach ($finder as $file) {
            $data = Yaml::parseFile($file->getRealPath());
            
            // 3. Validate required fields
            $this->validateRequiredFields($data, $file->getFilename());
            
            // 4. Create entity
            $benchmark = new Benchmark(
                slug: $data['slug'],
                name: $data['name'],
                category: $data['category'],
                description: $data['description'] ?? '',
                code: trim($data['code']),
                phpVersions: $data['phpVersions'],
                tags: $data['tags'] ?? [],
                icon: $data['icon'] ?? null
            );
            
            // 5. Persist
            $manager->persist($benchmark);
        }

        $manager->flush();
    }
}
```

## Database Schema

```sql
CREATE TABLE benchmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    code TEXT NOT NULL,
    tags JSON NOT NULL,
    icon VARCHAR(10) DEFAULT NULL,
    php_versions JSON NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_category (category),
    INDEX idx_slug (slug)
);
```

## Best Practices

### 1. Naming Conventions

```yaml
# ✅ Good
slug: array-fill-with-value
name: 'Array Fill with Value'

# ❌ Bad
slug: ArrayFillWithValue
name: 'array fill'
```

### 2. Categories

Use consistent category names:

```yaml
# Standard categories
category: 'Array Operations'
category: 'String Operations'
category: 'Loop Performance'
category: 'OOP Performance'
category: 'Function Calls'
```

### 3. PHP Version Selection

```yaml
# ✅ Test modern PHP only
phpVersions: [php82, php83, php84, php85]

# ✅ Test all PHP versions
phpVersions: [php56, php70, php71, php72, php73, php74, php80, php81, php82, php83, php84, php85]

# ✅ Test specific feature (e.g., PHP 8.0+ named arguments)
phpVersions: [php80, php81, php82, php83, php84, php85]
```

### 4. Code Guidelines

```yaml
code: |
  // Use consistent iteration count
  for ($i = 0; $i < 10000; $i++) {
      // Benchmark code here
  }
```

**Tips:**
- Use high iteration counts (10,000+) for accurate timing
- Avoid I/O operations (file, network, database)
- Keep code focused on one performance aspect
- Add comments explaining what's being tested

### 5. Tags

```yaml
tags:
  - array          # Data structure
  - initialization # Operation type
  - memory         # Performance aspect
```

## Troubleshooting

### Problem: Fixture not loading

**Check:**
1. YAML syntax is valid: `php bin/console lint:yaml fixtures/benchmarks/my-benchmark.yaml`
2. All required fields are present
3. File extension is `.yaml` or `.yml`
4. File is in `fixtures/benchmarks/` directory

**Solution:**
```bash
# Check logs
docker-compose logs main | grep "Failed to load"

# Validate YAML manually
docker-compose run --rm main php bin/console lint:yaml fixtures/benchmarks/*.yaml
```

### Problem: Duplicate slug error

**Error**: `Duplicate entry 'my-slug' for key 'UNIQ_41BC1C58989D9B62'`

**Solution**: Each `slug` must be unique. Change the slug in your YAML file.

### Problem: Invalid PHP version

**Error**: `ValueError: "php90" is not a valid backing value for enum`

**Solution**: Use valid PHP version values (`php56` to `php85`).

### Problem: Empty code

**Error**: `Code field cannot be empty in my-benchmark.yaml`

**Solution**: Add actual PHP code in the `code:` field.

## Migration from Old System

### Before (PHP Classes)

```php
// src/Benchmark/Pulse/MyBenchmark.php
final class MyBenchmark extends AbstractBenchmark
{
    #[All]
    public function execute(): void
    {
        // Code here
    }
}
```

### After (YAML Fixtures)

```yaml
# fixtures/benchmarks/my-benchmark.yaml
slug: my-benchmark
name: 'My Benchmark'
category: 'Tests'
phpVersions: [php56, php70, php84, php85]  # Replaces #[All]
code: |
  // Code here (same as execute() body)
```

**Benefits:**
- No PHP class needed
- Easier to edit
- Version controlled
- Can be loaded/reloaded without code changes

## Related Documentation

- [Creating Benchmarks Guide](creating-benchmarks.md) - Full guide with examples
- [Architecture Overview](../architecture/01-overview.md) - Clean Architecture principles
- [CLAUDE.md](../../CLAUDE.md) - Developer reference with all commands

## Next Steps

1. Browse existing fixtures: `fixtures/benchmarks/*.yaml`
2. Create your own benchmark YAML file
3. Load with `make fixtures`
4. Run with `make run test=YourSlug`
5. View results at `http://localhost/dashboard`
