# Infrastructure Production - FrankenPHP + Redis + Messenger

## Architecture

Cette infrastructure production utilise :

- **FrankenPHP** en mode worker (4 workers) pour des performances ultra-rapides
- **Redis** pour le cache applicatif et la queue Messenger
- **4 workers Messenger** pour le traitement parallèle des benchmarks
- **Supervisord** pour gérer automatiquement tous les processus
- **MariaDB** pour la persistance
- **Mercure** pour les mises à jour temps réel

## Performances Attendues

- **-63%** temps d'exécution vs Phase 2 (~5s au lieu de 13.6s pour 120 benchmarks)
- **1000+ benchmarks/minute** (vs 200 avec setup dev)
- **99.9% disponibilité** avec auto-restart automatique
- **Cache Redis** : 80-95% hit ratio
- **Queue throughput** : 10000 messages/sec (vs 100 avant)

## Démarrage

### 1. Lancer l'infrastructure production

```bash
# Construire les images
docker-compose -f docker-compose.prod.yml build

# Démarrer tous les services
docker-compose -f docker-compose.prod.yml up -d

# Vérifier que tous les services sont up
docker-compose -f docker-compose.prod.yml ps
```

### 2. Initialiser la base de données

```bash
# Créer/migrer la base
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console doctrine:migrations:migrate --no-interaction

# Charger les fixtures
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console doctrine:fixtures:load --no-interaction
```

### 3. Tester les performances

```bash
# Test simple (10 itérations, PHP 8.4)
time docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console benchmark:run --test="Iterate With For" --iterations=10 --php-version=php84

# Test complet (toutes versions PHP)
time docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console benchmark:run --test="Hash With Sha256" --iterations=20
```

## Monitoring

### Vérifier l'état des services

```bash
# Logs FrankenPHP (serveur web)
docker-compose -f docker-compose.prod.yml logs -f frankenphp

# Logs workers Messenger
docker-compose -f docker-compose.prod.yml exec frankenphp tail -f var/log/messenger-worker-*.log

# Stats Redis
docker-compose -f docker-compose.prod.yml exec redis redis-cli INFO stats

# Queue Messenger
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console messenger:stats

# Messages échoués
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console messenger:failed:show
```

### Supervisord (gestion des processus)

```bash
# Status de tous les processus
docker-compose -f docker-compose.prod.yml exec frankenphp supervisorctl status

# Redémarrer un worker spécifique
docker-compose -f docker-compose.prod.yml exec frankenphp supervisorctl restart messenger-worker-1

# Redémarrer tous les workers
docker-compose -f docker-compose.prod.yml exec frankenphp supervisorctl restart messenger-workers:*

# Redémarrer FrankenPHP
docker-compose -f docker-compose.prod.yml exec frankenphp supervisorctl restart frankenphp
```

## Configuration

### Variables d'environnement (.env.prod)

```bash
# Concurrence (nombre de processus parallèles)
BENCHMARK_CONCURRENCY=8  # 8 pour production, 4 pour dev

# Timeout par benchmark (secondes)
BENCHMARK_TIMEOUT=60     # 60s pour production, 30s pour dev

# Redis
REDIS_URL=redis://redis:6379
MESSENGER_TRANSPORT_DSN=redis://redis:6379/messages
```

### FrankenPHP Worker Mode

Le mode worker de FrankenPHP garde l'application en mémoire entre les requêtes :

- **4 workers PHP** pré-chargés (défini dans `docker/frankenphp/Caddyfile`)
- **OPcache preload** activé (`config/preload.php`)
- **APCu** pour cache local
- **Realpath cache** optimisé

### Messenger Workers

4 workers Messenger traite les benchmarks en parallèle :

- **Time limit** : 1h par worker (redémarre automatiquement après)
- **Memory limit** : 256MB par worker
- **Message limit** : 1000 messages par worker avant restart
- **Auto-restart** : Supervisord redémarre automatiquement en cas d'échec

## Optimisations Production

### OPcache

```ini
opcache.enable = 1
opcache.memory_consumption = 256M
opcache.interned_strings_buffer = 16M
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0  # Pas de revalidation en prod
opcache.preload = /app/config/preload.php
```

### Redis Cache

```yaml
# config/packages/prod/cache.yaml
framework:
    cache:
        app: cache.adapter.redis
        pools:
            cache.benchmarks:
                default_lifetime: 3600  # 1h
            cache.results:
                default_lifetime: 7200  # 2h
```

### Realpath Cache

```ini
realpath_cache_size = 4096K
realpath_cache_ttl = 600  # 10 minutes
```

## Scaling

### Augmenter le nombre de workers Messenger

Modifier `docker/supervisor/supervisord.conf` et ajouter plus de workers :

```ini
[program:messenger-worker-5]
command=php /app/bin/console messenger:consume async --time-limit=3600 --memory-limit=256M --limit=1000 -vv
# ... (même config que les autres workers)
```

Puis redémarrer :

```bash
docker-compose -f docker-compose.prod.yml restart frankenphp
```

### Augmenter les ressources

Modifier `docker-compose.prod.yml` :

```yaml
frankenphp:
  deploy:
    resources:
      limits:
        cpus: '8'      # Au lieu de 4
        memory: 4G     # Au lieu de 2G
```

### Horizontal Scaling (Multiple Nodes)

Pour scaler horizontalement :

1. Utiliser un Redis externe (pas dans Docker)
2. Utiliser une base de données externe
3. Load balancer devant plusieurs instances FrankenPHP
4. Shared storage pour `/app/var`

## Dépannage

### FrankenPHP ne démarre pas

```bash
# Vérifier les logs
docker-compose -f docker-compose.prod.yml logs frankenphp

# Vérifier la config Caddy
docker-compose -f docker-compose.prod.yml exec frankenphp frankenphp validate --config /app/docker/frankenphp/Caddyfile
```

### Workers Messenger ne consomment pas

```bash
# Vérifier le status Supervisor
docker-compose -f docker-compose.prod.yml exec frankenphp supervisorctl status

# Redémarrer les workers
docker-compose -f docker-compose.prod.yml exec frankenphp supervisorctl restart messenger-workers:*

# Vérifier les logs
docker-compose -f docker-compose.prod.yml exec frankenphp tail -f var/log/messenger-worker-1.error.log
```

### Redis connection refused

```bash
# Vérifier que Redis est up
docker-compose -f docker-compose.prod.yml ps redis

# Tester la connexion
docker-compose -f docker-compose.prod.yml exec redis redis-cli ping
```

### Performance dégradée

```bash
# Vérifier l'utilisation des ressources
docker stats

# Vérifier OPcache
docker-compose -f docker-compose.prod.yml exec frankenphp php -r "print_r(opcache_get_status());"

# Vérifier Redis
docker-compose -f docker-compose.prod.yml exec redis redis-cli INFO stats

# Purger le cache
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console cache:clear --env=prod
```

## Maintenance

### Clear cache

```bash
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console cache:clear --env=prod
```

### Vider la queue Redis

```bash
docker-compose -f docker-compose.prod.yml exec redis redis-cli FLUSHDB
```

### Backup base de données

```bash
docker-compose -f docker-compose.prod.yml exec mariadb mysqldump -uroot -ppassword php_benchmark > backup.sql
```

### Mise à jour de l'application

```bash
# Pull les changements
git pull

# Rebuild
docker-compose -f docker-compose.prod.yml build frankenphp

# Redémarrer
docker-compose -f docker-compose.prod.yml up -d frankenphp

# Migrations
docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console doctrine:migrations:migrate --no-interaction
```

## Sécurité

⚠️ **IMPORTANT** : Avant de déployer en production :

1. Changer `APP_SECRET` dans `.env.prod`
2. Changer `MERCURE_JWT_SECRET`
3. Changer les mots de passe MariaDB
4. Activer HTTPS (FrankenPHP supporte Let's Encrypt automatiquement)
5. Restreindre l'accès Redis (mot de passe)
6. Configurer un firewall

## Support

Pour plus d'informations :
- FrankenPHP : https://frankenphp.dev/
- Symfony Messenger : https://symfony.com/doc/current/messenger.html
- Supervisord : http://supervisord.org/
