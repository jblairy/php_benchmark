# ADR-005: Enforce PHPStan Level Max

**Status**: Accepted  
**Date**: 2024-08-26  
**Deciders**: Development Team

## Context

PHP is a dynamically typed language, allowing variables to change types at runtime. This flexibility leads to:

- **Type-related bugs** (passing string to method expecting int)
- **Null pointer exceptions** (calling methods on null)
- **Array key errors** (accessing non-existent array indices)
- **Method signature mismatches** (incorrect parameter types)

These errors often surface only at runtime, sometimes in production, leading to poor reliability and difficult debugging.

### Project Requirements
- **Type safety**: Catch type errors during development, not production
- **Refactoring confidence**: Safely change code without breaking existing functionality
- **Documentation**: Code should be self-documenting through type declarations
- **Maintainability**: Prevent future developers from introducing type-unsafe code

### Options Considered

1. **No static analysis**: Rely on manual testing and runtime errors
2. **PHPStan Level 5** (default): Basic type checking with reasonable strictness
3. **PHPStan Level 8**: Advanced checks including unused variables
4. **PHPStan Level 9 (Max)**: Strictest analysis, enforces mixed type declarations

## Decision

We enforce **PHPStan Level 9 (Max)** with zero errors allowed because:

1. **Maximum type safety**: Catches every possible type-related issue PHPStan can detect
2. **Explicit mixed types**: Forces developers to acknowledge when types are truly unknown
3. **No assumptions**: PHPStan makes zero assumptions about missing type hints
4. **Refactoring confidence**: Type system guarantees prevent accidental breakage
5. **Future-proof**: Already at highest level, won't need incremental upgrades

### Configuration

```neon
# phpstan.dist.neon
parameters:
    level: max
    paths:
        - src
        - tests
    
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: true
    checkBenevolentUnionTypes: true
    strictRules: true
```

### Requirements for Developers

All code must:
- Declare **all parameter types** (`function foo(string $bar)`)
- Declare **all return types** (`function foo(): int`)
- Declare **all property types** (`private string $name`)
- Use `mixed` explicitly when type truly unknown (`function foo(mixed $data)`)
- Add `@param` and `@return` PHPDoc for complex types (arrays, generics)

Example:
```php
final readonly class StatisticsCalculator
{
    /**
     * @param array<int, float> $executionTimes
     * @return array{min: float, max: float, avg: float}
     */
    public function calculate(array $executionTimes): array
    {
        return [
            'min' => min($executionTimes),
            'max' => max($executionTimes),
            'avg' => array_sum($executionTimes) / count($executionTimes),
        ];
    }
}
```

## Consequences

### Positive
- **Fewer bugs**: Type errors caught before code reaches production
- **Better IDE support**: Full autocomplete and refactoring tools
- **Self-documenting**: Types serve as always-up-to-date documentation
- **Refactoring confidence**: Change method signatures with guaranteed type safety
- **Code quality**: Forces developers to think about types explicitly
- **CI/CD integration**: Automated type checking in every pull request

### Negative
- **Initial effort**: Requires adding type hints to all existing code
- **Learning curve**: Developers must understand PHPDoc annotations for complex types
- **False positives**: Occasionally PHPStan reports issues that are actually safe
- **Development friction**: Cannot merge code with type errors, even if functionally correct

### Trade-offs Accepted
- We accept occasional false positives for overall type safety
- We accept development friction as necessary for quality enforcement
- We use `@phpstan-ignore-next-line` sparingly for unavoidable false positives
- We prioritize long-term maintainability over short-term development speed

## Alternatives Not Chosen

### No Static Analysis
**Rejected**: Unacceptable risk of production bugs, poor developer experience

### PHPStan Level 5-8
```neon
level: 5
```
**Rejected**: Leaves gaps in type safety, would eventually need to upgrade to max anyway

### Psalm/Phan
**Rejected**: PHPStan has better Symfony integration, larger community, more active development

## CI/CD Integration

PHPStan runs automatically on every commit via GitHub Actions:

```yaml
# .github/workflows/quality.yml
- name: PHPStan
  run: vendor/bin/phpstan analyse --no-progress --error-format=github
```

Pull requests cannot merge if PHPStan fails.

## Suppressing False Positives

When PHPStan incorrectly reports an error (rare), use inline suppression:

```php
// @phpstan-ignore-next-line argument.type
$result = someThirdPartyLib($value);
```

**Rule**: Suppression must include:
1. Specific error identifier (e.g., `argument.type`)
2. Inline comment explaining why suppression needed

## Future Improvements

1. **Custom rules**: Add project-specific PHPStan rules (e.g., enforce Port naming convention)
2. **Baseline reduction**: Gradually eliminate any remaining baseline suppressions
3. **Generic annotations**: Leverage PHPStan generics for collection types

## References
- [PHPStan Documentation](https://phpstan.org/user-guide/rule-levels)
- [Why Level Max?](https://phpstan.org/blog/find-bugs-in-your-code-without-writing-tests)
- Configuration: `phpstan.dist.neon`
- Makefile command: `make phpstan`
