<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

/**
 * Helper to exclude native PHP classes from architecture rules.
 * Native PHP classes (exceptions, attributes, reflection) are acceptable dependencies.
 */
function allowPhpNativeClasses(string ...$allowedNamespaces): NotHaveDependencyOutsideNamespace
{
    $nativePhpClasses = [
        // PHP Native Exceptions
        'Exception',
        'RuntimeException',
        'InvalidArgumentException',
        'LogicException',
        'DomainException',

        // PHP Native Classes
        'Attribute',
        'ReflectionClass',
        'ReflectionMethod',
        'ReflectionParameter',
        'DateTimeImmutable',
        'DateTime',
        'stdClass',
    ];

    return new NotHaveDependencyOutsideNamespace($allowedNamespaces[0], array_merge(array_slice($allowedNamespaces, 1), $nativePhpClasses));
}

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__ . '/src');

    $rules = [];

    // ========================================
    // VIOLATIONS DÉTECTÉES PAR PHPARKITECT
    // ========================================
    //
    // ✅ VIOLATIONS ACCEPTABLES (Classes natives PHP):
    // - Exceptions: RuntimeException, InvalidArgumentException, etc.
    // - Réflexion: ReflectionClass, ReflectionMethod
    // - Attribute, stdClass, et autres classes PHP standard
    //
    // ❌ VIOLATIONS CRITIQUES À CORRIGER:
    //
    // 1. SingleBenchmarkExecutor (Domain/Benchmark/Service) ligne 19
    //    → Dépend de InstrumentedScriptBuilder (Infrastructure)
    //    FIX: Créer ScriptBuilderPort dans Domain/Benchmark/Port
    //
    // 2. ChartBuilder (Application/Service)
    //    → Dépend de Symfony\UX\Chartjs (Framework externe)
    //    FIX: Déplacer ChartBuilder vers Infrastructure OU créer un Port
    //

    //
    // 4. Benchmark interface (Domain/Benchmark/Contract) ligne 10
    //    → Dépend de Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag
    //    FIX: Retirer l'annotation Symfony et configurer le tag dans services.yaml
    //
    // 5. AbstractBenchmark (Domain/Benchmark/Contract) ligne 41
    //    → Dépend de Jblairy\PhpBenchmark\Benchmark\Exception (ancien namespace)
    //    FIX: Corriger le namespace vers Jblairy\PhpBenchmark\Domain\Benchmark\Exception

    // ========================================
    // CLEAN ARCHITECTURE - LAYER RULES
    // ========================================

    // Rule 1: Domain Layer - NO external dependencies (except native PHP)
    // Le Domain ne doit dépendre de RIEN (ni Application, ni Infrastructure, ni frameworks)
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Jblairy\PhpBenchmark\Domain'))
        ->should(allowPhpNativeClasses('Jblairy\PhpBenchmark\Domain'))
        ->because('Domain must not depend on Application or Infrastructure - Clean Architecture principle');

    // Rule 2: Application Layer - can only depend on Domain (except native PHP)
    // L'Application peut utiliser le Domain, mais pas l'Infrastructure
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Jblairy\PhpBenchmark\Application'))
        ->should(allowPhpNativeClasses('Jblairy\PhpBenchmark\Application', 'Jblairy\PhpBenchmark\Domain'))
        ->because('Application layer can only depend on Domain layer, not on Infrastructure');

    // Rule 3: Infrastructure can depend on Domain and Application (but Application should be minimal)
    // L'Infrastructure implémente les Ports du Domain
    // Pas de règle restrictive car Infrastructure peut tout utiliser

    // ========================================
    // HEXAGONAL ARCHITECTURE - PORT/ADAPTER
    // ========================================

    // Rule 4: Ports (interfaces) must be in Domain
    // Les Ports (interfaces) doivent être dans le Domain
    $rules[] = Rule::allClasses()
        ->that(new HaveNameMatching('*Port'))
        ->should(new ResideInOneOfTheseNamespaces('Jblairy\PhpBenchmark\Domain'))
        ->because('Ports (interfaces) must reside in Domain layer - Hexagonal Architecture');

    // Rule 5: Adapters (implementations) must be in Infrastructure
    // Les implémentations concrètes des Ports doivent être dans Infrastructure
    // Note: Cette règle est implicite par les règles de couches

    // ========================================
    // DDD - DOMAIN-DRIVEN DESIGN RULES
    // ========================================

    // Rule 6: Repository interfaces must be Ports in Domain, implementations in Infrastructure
    // Les interfaces Repository doivent être des Ports dans Domain, les implémentations dans Infrastructure
    $rules[] = Rule::allClasses()
        ->that(new HaveNameMatching('*Repository'))
        ->should(new ResideInOneOfTheseNamespaces(
            'Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Repository',
            'Jblairy\PhpBenchmark\Infrastructure\Persistence\InMemory',
            'Jblairy\PhpBenchmark\Domain\Benchmark\Port',
        ))
        ->because('Repositories must be in Infrastructure (concrete) or Domain/Port (interface)');

    // Rule 7: Domain Models must not have framework annotations
    // Les modèles du Domain ne doivent pas avoir d'annotations Doctrine/Symfony
    // Note: Cette règle nécessiterait une expression personnalisée pour vérifier les annotations

    // Rule 8: Value Objects and Entities should be in Domain
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Jblairy\PhpBenchmark\Domain\Benchmark\Model'))
        ->should(allowPhpNativeClasses('Jblairy\PhpBenchmark\Domain'))
        ->because('Domain Models must not depend on external layers');

    // ========================================
    // SPECIFIC PROJECT RULES
    // ========================================

    // Rule 9: Benchmark Tests must be in Domain
    // Les tests de benchmark sont des concepts métier
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Jblairy\PhpBenchmark\Domain\Benchmark\Test'))
        ->should(allowPhpNativeClasses('Jblairy\PhpBenchmark\Domain'))
        ->because('Benchmark tests are domain concepts and must not depend on external layers');

    // Rule 10: Controllers must be in Infrastructure/Web
    $rules[] = Rule::allClasses()
        ->that(new HaveNameMatching('*Controller'))
        ->should(new ResideInOneOfTheseNamespaces('Jblairy\PhpBenchmark\Infrastructure\Web\Controller'))
        ->because('Controllers are Infrastructure concerns - they handle HTTP');

    // Rule 11: Use Cases must be in Application and only depend on Domain
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Jblairy\PhpBenchmark\Application\UseCase'))
        ->should(allowPhpNativeClasses('Jblairy\PhpBenchmark\Application', 'Jblairy\PhpBenchmark\Domain'))
        ->because('Use Cases orchestrate domain logic and must not depend on Infrastructure');

    // Rule 12: Commands must be in Infrastructure/Cli
    $rules[] = Rule::allClasses()
        ->that(new HaveNameMatching('*Command'))
        ->should(new ResideInOneOfTheseNamespaces('Jblairy\PhpBenchmark\Infrastructure\Cli'))
        ->because('Commands are Infrastructure concerns - they handle CLI interactions');

    // Rule 13: Domain Services must not depend on Infrastructure
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Jblairy\PhpBenchmark\Domain\Benchmark\Service'))
        ->should(allowPhpNativeClasses('Jblairy\PhpBenchmark\Domain'))
        ->because('Domain Services must remain pure and only depend on Domain layer');

    // Rule 14: Entities (Doctrine) must be in Infrastructure
    $rules[] = Rule::allClasses()
        ->that(new HaveNameMatching('*Entity'))
        ->should(new ResideInOneOfTheseNamespaces('Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity'))
        ->because('Doctrine entities are Infrastructure concerns with framework dependencies');

    // ========================================
    // ANTI-CORRUPTION LAYER RULES
    // ========================================

    // Rule 15: Adapters/Implementations must be in Infrastructure
    // Les adaptateurs (implémentations des Ports) doivent être dans Infrastructure
    $rules[] = Rule::allClasses()
        ->that(new HaveNameMatching('*Adapter'))
        ->should(new ResideInOneOfTheseNamespaces('Jblairy\PhpBenchmark\Infrastructure'))
        ->because('Adapters are Infrastructure implementations of Domain ports');

    // ========================================
    // RÈGLES ADDITIONNELLES POUR DDD/HEXA
    // ========================================

    // Rule 17: Contracts/Interfaces must be in Domain
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Jblairy\PhpBenchmark\Domain\Benchmark\Contract'))
        ->should(allowPhpNativeClasses('Jblairy\PhpBenchmark\Domain'))
        ->because('Contracts are core domain concepts and must not depend on external layers');

    // Rule 18: Application Services must be in Application and only depend on Domain
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('Jblairy\PhpBenchmark\Application\Service'))
        ->should(allowPhpNativeClasses('Jblairy\PhpBenchmark\Application', 'Jblairy\PhpBenchmark\Domain'))
        ->because('Application Services must not depend on Infrastructure or external frameworks');

    $config->add($classSet, ...$rules);
};

// ========================================
// UTILISATION
// ========================================
//
// Vérifier les violations:
//   docker-compose run --rm main vendor/bin/phparkitect check
//
// Comprendre le rapport:
//   - 84 violations totales détectées (incluant les classes natives PHP)
//   - 5 violations critiques à corriger (voir liste ci-dessus)
//   - Les violations natives PHP (Attribute, Exception, Reflection) sont acceptables
//
// Prochaines étapes:
//   1. Corriger SingleBenchmarkExecutor (PRIORITÉ 1 - Domain dépend d'Infrastructure)
//   2. Corriger AbstractBenchmark namespace (PRIORITÉ 2 - Simple refactor)
//   3. Corriger Benchmark interface (PRIORITÉ 3 - Retirer annotation Symfony)
//   4. Refactorer ChartBuilder (PRIORITÉ 4 - Le déplacer ou créer Port)

