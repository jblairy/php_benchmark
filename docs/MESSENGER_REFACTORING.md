# Refactoring: Migration vers Symfony Messenger

## Résumé

Ce document décrit la refactoring effectuée pour migrer complètement le système d'exécution asynchrone des benchmarks vers Symfony Messenger, en supprimant l'ancienne implémentation basée sur `AsyncExecutorPort`.

## Problème identifié

Le code contenait deux systèmes d'exécution asynchrone qui cohabitaient :

1. **Ancien système** : `AsyncBenchmarkRunner` + `AsyncExecutorPort`
   - Utilisait soit `SpatieAsyncExecutorAdapter` (processus PHP)
   - Soit un faux `MessengerAsyncExecutorAdapter` qui exécutait en réalité de manière synchrone

2. **Nouveau système** : Symfony Messenger
   - `ExecuteBenchmarkMessage` et `ExecuteBenchmarkHandler` déjà en place
   - Workers Messenger configurés dans supervisor
   - Mais n'était pas utilisé !

## Changements effectués

### 1. Création de `MessengerBenchmarkRunner`

Nouveau runner qui remplace `AsyncBenchmarkRunner` :
- Dispatch un message `ExecuteBenchmarkMessage` pour chaque itération
- Utilise directement le `MessageBusInterface` de Symfony
- Plus simple et plus direct que l'ancienne implémentation

### 2. Refactoring de `BenchmarkOrchestrator`

- Remplacé `AsyncBenchmarkRunner` par `MessengerBenchmarkRunner`
- Aucun changement dans l'interface publique
- Fonctionne de manière identique pour les utilisateurs

### 3. Suppression des anciennes classes

Classes supprimées :
- `AsyncBenchmarkRunner`
- `AsyncExecutorPort` (interface du Domain)
- `SpatieAsyncExecutorAdapter`
- `MessengerAsyncExecutorAdapter` (fausse implémentation)
- `AsyncBenchmarkRunnerTest`

### 4. Nettoyage de la configuration

- Supprimé la configuration de `AsyncExecutorPort` dans `services.yaml`
- Nettoyé les références dans `phparkitect.php`

## Architecture finale

```
BenchmarkCommand
    ↓
BenchmarkOrchestrator
    ↓
MessengerBenchmarkRunner
    ↓
MessageBus::dispatch(ExecuteBenchmarkMessage)
    ↓
[Queue Async]
    ↓
ExecuteBenchmarkHandler (dans un worker)
    ↓
BenchmarkExecutorPort::execute()
```

## Avantages

1. **Une seule implémentation** : Plus de confusion entre deux systèmes
2. **Production-ready** : Utilise Symfony Messenger avec retry, monitoring, etc.
3. **Scalable** : 4 workers configurés dans supervisor
4. **Clean Architecture respectée** : Pas de dépendance externe dans le Domain

## Comment tester

1. Lancer un benchmark :
   ```bash
   php bin/console benchmark:run --test=Loop --iterations=10 --php-version=php84
   ```

2. Vérifier les workers :
   ```bash
   supervisorctl status
   ```

3. Voir les logs des workers :
   ```bash
   tail -f var/log/messenger-worker-*.log
   ```

## Migration pour les développeurs

Aucune action requise. Le système fonctionne de manière identique mais utilise maintenant Messenger de bout en bout.