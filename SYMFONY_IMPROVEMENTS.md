# 🎵 SYMFONY IMPROVEMENTS - ACTION PLAN

**Priority**: Implement in phases  
**Total Effort**: 8-12 hours  
**Expected Score Improvement**: 7.5 → 8.5-9.0/10  

---

## 🔴 PHASE 1: CRITICAL FIXES (1-2 hours)

### 1.1 Remove Unused Bundles

**File**: `composer.json`

```bash
composer remove symfony/form symfony/notifier symfony/mailer symfony/http-client
```

**Impact**:
- ✅ Reduces bundle size
- ✅ Improves container compilation time
- ✅ Reduces memory footprint
- ✅ Removes unused security surface

**Verification**:
```bash
composer show | grep -E "form|notifier|mailer|http-client"
# Should return nothing
```

---

### 1.2 Fix Class Loader Configuration

**File**: `config/services.yaml`

**Current**:
```yaml
parameters:
    container.dumper.inline_class_loader: false
```

**Change to**:
```yaml
parameters:
    container.dumper.inline_class_loader: false

when@prod:
    parameters:
        container.dumper.inline_class_loader: true
```

**Impact**:
- ✅ Improves production performance
- ✅ Reduces container size
- ✅ Faster class loading

---

### 1.3 Configure Production Cache

**File**: `config/packages/cache.yaml`

**Current**:
```yaml
framework:
    cache:
        # Redis commented out
        #app: cache.adapter.redis
```

**Change to**:
```yaml
framework:
    cache:
        # Unique name of your app
        prefix_seed: jblairy/php_benchmark

        # The "app" cache stores to the filesystem by default
        # The data in this cache should persist between deploys

when@prod:
    framework:
        cache:
            app: cache.adapter.redis
            default_redis_provider: '%env(REDIS_URL)%'
            pools:
                cache.benchmarks:
                    adapter: cache.adapter.redis
                cache.results:
                    adapter: cache.adapter.redis
```

**Add to `.env`**:
```env
# Redis cache (production)
REDIS_URL=redis://redis:6379
```

**Impact**:
- ✅ Significant performance improvement in production
- ✅ Distributed caching support
- ✅ Better scalability

---

### 1.4 Externalize Hardcoded Locale

**File**: `config/services.yaml`

**Current**:
```yaml
parameters:
    locale: 'fr'
```

**Change to**:
```yaml
parameters:
    locale: '%env(string:APP_LOCALE)%'
```

**Add to `.env`**:
```env
APP_LOCALE=fr
```

**Add to `.env.prod`** (if different):
```env
APP_LOCALE=en
```

**Impact**:
- ✅ Configuration is now environment-specific
- ✅ Easier to deploy to different locales
- ✅ Follows 12-factor app principles

---

## 🟡 PHASE 2: CORE IMPROVEMENTS (3-4 hours)

### 2.1 Add Exception Handling in Controllers

**File**: `src/Infrastructure/Web/Controller/DashboardController.php`

**Current**:
```php
#[Route('/dashboard', name: 'app_dashboard')]
public function dashboard(): Response
{
    return $this->render('dashboard/index.html.twig', [
        'stats' => $this->benchmarkRepositoryPort->getDashboardStats(),
        'top_categories' => $this->benchmarkRepositoryPort->getTopCategories(3),
    ]);
}
```

**Change to**:
```php
#[Route('/dashboard', name: 'app_dashboard')]
public function dashboard(): Response
{
    try {
        $stats = $this->benchmarkRepositoryPort->getDashboardStats();
        $topCategories = $this->benchmarkRepositoryPort->getTopCategories(3);
    } catch (RuntimeException $e) {
        $this->logger->error('Failed to load dashboard stats', [
            'error' => $e->getMessage(),
            'exception' => $e,
        ]);
        $this->addFlash('error', 'Failed to load dashboard statistics');
        return $this->redirectToRoute('app_dashboard');
    }

    $response = $this->render('dashboard/index.html.twig', [
        'mercure_public_url' => $this->mercurePublicUrl,
        'stats' => $stats,
        'top_categories' => $topCategories,
    ]);

    // Cache for 5 minutes
    $response->setSharedMaxAge(300);
    $response->setPublic();

    return $response;
}
```

**Add to constructor**:
```php
use Psr\Log\LoggerInterface;

public function __construct(
    private readonly BenchmarkRepositoryPort $benchmarkRepositoryPort,
    #[Autowire(env: 'MERCURE_PUBLIC_URL')]
    private readonly string $mercurePublicUrl,
    private readonly LoggerInterface $logger,
) {
}
```

**Impact**:
- ✅ Graceful error handling
- ✅ Better logging for debugging
- ✅ HTTP caching for performance
- ✅ User-friendly error messages

---

### 2.2 Add Error Handling in Mercure Subscriber

**File**: `src/Infrastructure/Mercure/EventSubscriber/BenchmarkProgressSubscriber.php`

**Current**:
```php
private function publishUpdate(string $topic, array $data): void
{
    $update = new Update(
        $topic,
        json_encode($data, JSON_THROW_ON_ERROR),
    );

    $this->hub->publish($update);
}
```

**Change to**:
```php
use Psr\Log\LoggerInterface;

final readonly class BenchmarkProgressSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private HubInterface $hub,
        private LoggerInterface $logger,
    ) {
    }

    private function publishUpdate(string $topic, array $data): void
    {
        try {
            $update = new Update(
                $topic,
                json_encode($data, JSON_THROW_ON_ERROR),
            );

            $this->hub->publish($update);
        } catch (Exception $e) {
            $this->logger->error('Failed to publish Mercure update', [
                'topic' => $topic,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            // Don't re-throw - allow benchmark to continue even if Mercure fails
        }
    }
}
```

**Impact**:
- ✅ Graceful degradation if Mercure fails
- ✅ Better observability
- ✅ Prevents benchmark interruption

---

### 2.3 Add Query Result Caching

**File**: `src/Infrastructure/Persistence/Doctrine/Repository/DoctrineBenchmarkRepository.php`

**Add to constructor**:
```php
use Symfony\Contracts\Cache\CacheInterface;

final readonly class DoctrineBenchmarkRepository implements BenchmarkRepositoryPort
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
    ) {
    }
```

**Update methods**:
```php
public function getDashboardStats(): DashboardStats
{
    return $this->cache->get('dashboard_stats', function () {
        $totalBenchmarks = $this->countTotalBenchmarks();
        $result = $this->fetchPulseStatistics($totalBenchmarks);

        if (!$result instanceof DashboardStats) {
            throw new RuntimeException('Unexpected result type from Doctrine SELECT NEW query');
        }

        return $result;
    });
}

public function getTopCategories(int $limit = 3): array
{
    return $this->cache->get('top_categories_' . $limit, function () use ($limit) {
        $results = $this->entityManager
            ->createQuery('
                SELECT b.category, COUNT(b.id) as benchmark_count
                FROM ' . BenchmarkEntity::class . ' b
                GROUP BY b.category
                ORDER BY benchmark_count DESC
            ')
            ->setMaxResults($limit)
            ->getResult();

        return $this->extractCategoryNamesFromQueryResults($results);
    });
}
```

**Register cache service** in `config/services.yaml`:
```yaml
Symfony\Contracts\Cache\CacheInterface:
    alias: cache.app
```

**Impact**:
- ✅ Significant performance improvement
- ✅ Reduced database queries
- ✅ Better scalability

---

### 2.4 Add Structured Logging

**File**: `src/Application/MessageHandler/ExecuteBenchmarkHandler.php`

**Add logging to key operations**:
```php
use Psr\Log\LoggerInterface;

final readonly class ExecuteBenchmarkHandler
{
    public function __construct(
        // ... existing dependencies
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ExecuteBenchmarkMessage $message): void
    {
        $benchmarkId = $message->benchmarkId;
        
        $this->logger->info('Benchmark execution started', [
            'benchmark_id' => $benchmarkId,
            'timestamp' => time(),
        ]);

        try {
            // ... existing code
            
            $this->logger->info('Benchmark execution completed', [
                'benchmark_id' => $benchmarkId,
                'duration' => time() - $startTime,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Benchmark execution failed', [
                'benchmark_id' => $benchmarkId,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
```

**Impact**:
- ✅ Better observability
- ✅ Easier debugging
- ✅ Performance monitoring

---

## 🟢 PHASE 3: PERFORMANCE OPTIMIZATION (4-5 hours)

### 3.1 Add Lazy Service Loading

**File**: `config/services.yaml`

**Current**:
```yaml
Jblairy\PhpBenchmark\Domain\Benchmark\Service\ConfigurableSingleBenchmarkExecutor: ~
```

**Change to**:
```yaml
Jblairy\PhpBenchmark\Domain\Benchmark\Service\ConfigurableSingleBenchmarkExecutor:
    lazy: true

Jblairy\PhpBenchmark\Domain\Dashboard\Service\OutlierDetector:
    lazy: true

Jblairy\PhpBenchmark\Domain\Dashboard\Service\StatisticsCalculator:
    lazy: true

Jblairy\PhpBenchmark\Infrastructure\Cli\Command\CalibrateIterationsCommand:
    lazy: true
```

**Impact**:
- ✅ Faster startup time
- ✅ Reduced memory usage
- ✅ Better performance for CLI commands

---

### 3.2 Add Component Caching

**File**: `src/Infrastructure/Web/Component/BenchmarkCardComponent.php`

**Add caching**:
```php
#[AsLiveComponent('BenchmarkCard')]
final class BenchmarkCardComponent
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $benchmarkId = '';

    #[LiveProp]
    public string $benchmarkName = '';

    // Cache component for 5 minutes
    public function getCache(): int
    {
        return 300;
    }

    // ... rest of the component
}
```

**Impact**:
- ✅ Reduced re-computation
- ✅ Better performance
- ✅ Smoother user experience

---

### 3.3 Add Error Boundaries in Components

**File**: `src/Infrastructure/Web/Component/BenchmarkCardComponent.php`

**Add error handling**:
```php
public function getData(): ?BenchmarkData
{
    try {
        if (null === $this->benchmarkData && '' !== $this->benchmarkId && '' !== $this->benchmarkName) {
            $this->benchmarkData = $this->getBenchmarkStatistics->execute($this->benchmarkId, $this->benchmarkName);
        }
    } catch (Exception $e) {
        // Log error but don't crash component
        error_log('Failed to load benchmark data: ' . $e->getMessage());
        return null;
    }

    return $this->benchmarkData;
}

public function getChart(): ?Chart
{
    try {
        if (!$this->chart instanceof Chart && $this->getData() instanceof BenchmarkData) {
            $this->chart = $this->chartBuilder->createBenchmarkChart(
                $this->getData(),
                $this->getAllPhpVersions(),
            );
        }
    } catch (Exception $e) {
        error_log('Failed to create chart: ' . $e->getMessage());
        return null;
    }

    return $this->chart;
}
```

**Impact**:
- ✅ Graceful error handling
- ✅ Better user experience
- ✅ Prevents UI crashes

---

### 3.4 Create Custom Exception Listener

**File**: `src/Infrastructure/Web/EventListener/ExceptionListener.php`

```php
<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Web\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final readonly class ExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Log the exception
        $this->logger->error('Unhandled exception', [
            'exception' => $exception,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
        ]);

        // Handle HTTP exceptions
        if ($exception instanceof HttpExceptionInterface) {
            $response = new Response(
                'Error: ' . $exception->getMessage(),
                $exception->getStatusCode(),
                $exception->getHeaders(),
            );
            $event->setResponse($response);
        }
    }
}
```

**Register in `config/services.yaml`**:
```yaml
Jblairy\PhpBenchmark\Infrastructure\Web\EventListener\ExceptionListener:
    tags:
        - { name: kernel.event_listener, event: kernel.exception }
```

**Impact**:
- ✅ Centralized error handling
- ✅ Consistent error responses
- ✅ Better logging

---

### 3.5 Add Validation to Controllers

**File**: `src/Infrastructure/Web/Controller/DashboardController.php`

**Add validation**:
```php
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly BenchmarkRepositoryPort $benchmarkRepositoryPort,
        #[Autowire(env: 'MERCURE_PUBLIC_URL')]
        private readonly string $mercurePublicUrl,
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        // Validate Mercure URL
        $errors = $this->validator->validate($this->mercurePublicUrl, [
            new Assert\Url(),
        ]);

        if (count($errors) > 0) {
            $this->logger->error('Invalid Mercure URL', [
                'url' => $this->mercurePublicUrl,
            ]);
            $this->addFlash('error', 'Configuration error');
            return $this->redirectToRoute('app_dashboard');
        }

        // ... rest of the method
    }
}
```

**Impact**:
- ✅ Input validation
- ✅ Better error detection
- ✅ Improved reliability

---

## 📋 VERIFICATION CHECKLIST

After implementing all phases, verify:

- [ ] Phase 1: All 4 critical fixes implemented
- [ ] Phase 2: All 4 core improvements implemented
- [ ] Phase 3: All 5 performance optimizations implemented
- [ ] Tests pass: `make test`
- [ ] Code quality: `make quality`
- [ ] No deprecations: `make phpstan`
- [ ] Performance improved: Measure response times

---

## 🎯 EXPECTED OUTCOMES

### Before Improvements
- Score: 7.5/10
- Unused bundles: 4
- Exception handling: None
- Caching: Minimal
- Logging: Minimal

### After Phase 1
- Score: 8.0/10
- Unused bundles: 0
- Performance: +10-15%

### After Phase 2
- Score: 8.5/10
- Exception handling: Complete
- Logging: Structured
- Reliability: Improved

### After Phase 3
- Score: 9.0/10
- Performance: +30-40%
- Caching: Comprehensive
- Optimization: Complete

---

## 📚 REFERENCES

- [Symfony Best Practices](https://symfony.com/doc/current/best_practices.html)
- [Symfony Service Container](https://symfony.com/doc/current/service_container.html)
- [Symfony Caching](https://symfony.com/doc/current/cache.html)
- [Symfony Event Dispatcher](https://symfony.com/doc/current/event_dispatcher.html)
- [Symfony Error Handling](https://symfony.com/doc/current/controller/error_pages.html)

---

**Implementation Time**: 8-12 hours  
**Expected Score**: 7.5 → 9.0/10  
**Priority**: High  
**Difficulty**: Medium  

Start with Phase 1 for quick wins! 🚀
