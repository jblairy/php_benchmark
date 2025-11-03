# ADR-002: Use Symfony Validator for YAML Fixtures

**Status**: Accepted  
**Date**: 2024-08-26  
**Deciders**: Development Team

## Context

The application loads benchmark definitions from YAML files in `fixtures/benchmarks/*.yaml`. Each fixture contains:

```yaml
name: "Loop Comparison"
category: "Control Structures"
icon: "🔄"
description: "Compare for vs foreach performance"
methods:
  - name: "For Loop"
    code: "for ($i = 0; $i < 1000; $i++) {}"
```

We need to validate:
- Required fields are present (name, category, methods)
- Data types are correct (strings, arrays)
- Constraints are met (max lengths, non-empty arrays)
- Business rules (unique method names, valid icon format)

### Options Considered

1. **Manual validation** with if/else checks in fixture loader
2. **Custom validation classes** with dedicated validator objects
3. **Symfony Validator** using attributes/annotations
4. **JSON Schema** with external validation library

## Decision

We use **Symfony Validator** with constraint attributes for several reasons:

1. **Already installed**: Symfony Validator is part of our Symfony stack
2. **Declarative syntax**: Constraints are defined as PHP attributes directly on DTOs
3. **Rich constraint library**: Built-in constraints for common validations (NotBlank, Length, Type, Count)
4. **Clear error messages**: Automatic generation of human-readable validation errors
5. **Reusable**: Same validation logic can be used for API inputs, forms, and fixtures

### Implementation

```php
final readonly class BenchmarkFixtureData
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name,
        
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $category,
        
        #[Assert\Count(min: 2)]
        #[Assert\All([
            new Assert\Type(MethodFixtureData::class),
        ])]
        public array $methods,
    ) {}
}
```

Validation happens in `YamlBenchmarkFixtures::validateFixtureData()` which throws `ValidationException` with detailed error messages.

## Consequences

### Positive
- **Reduced boilerplate**: No need to write manual if/else validation chains
- **Self-documenting**: Constraints make validation rules explicit and discoverable
- **Consistency**: Same validation approach across application (forms, API, fixtures)
- **Type safety**: Combined with PHP 8.4 strict types, catches errors early
- **Error messages**: Automatic generation of detailed validation feedback

### Negative
- **Framework coupling**: Fixture validation depends on Symfony Validator
- **Attribute verbosity**: Multiple attributes per property can be verbose
- **Limited customization**: Complex business rules may need custom constraints

### Trade-offs Accepted
- We accept Symfony dependency in Infrastructure layer (aligned with Hexagonal Architecture)
- We accept attribute verbosity for clarity and explicitness
- For complex validations, we supplement with custom validation methods

## Alternatives Not Chosen

### Manual Validation
```php
if (empty($data['name'])) {
    throw new \RuntimeException('Name is required');
}
```
**Rejected**: Too verbose, error-prone, hard to maintain

### JSON Schema
**Rejected**: Requires external library, separate schema files to maintain, less IDE support

## References
- [Symfony Validator Documentation](https://symfony.com/doc/current/validation.html)
- Implementation: `src/Infrastructure/Persistence/Doctrine/Fixtures/YamlBenchmarkFixtures.php`
