.PHONY: up start run fixtures db.reset db.refresh phpcsfixer phpcsfixer-fix phpstan quality phpmd phparkitect infection assets.refresh trans.compile trans.update dev.up dev.build dev.down dev.restart dev.logs dev.status dev.run prod.up prod.build prod.down prod.restart prod.logs prod.status prod.run ci.up ci.build ci.down ci.logs ci.test ci.quality calibrate calibrate-force calibrate-force-dry

# Default: Use dev environment
up:
	@echo "🚀 Starting development environment..."
	@echo "💡 Use 'make dev.up' for development or 'make prod.up' for production"
	docker-compose -f docker-compose.dev.yml up -d --remove-orphans

start:
	@echo "🏗️  Building development environment..."
	docker-compose -f docker-compose.dev.yml build

# Development commands (FrankenPHP + Redis + Messenger)
dev.build:
	@echo "🏗️  Building development infrastructure..."
	docker-compose -f docker-compose.dev.yml build
	@echo "✅ Development build complete"

dev.up:
	@echo "🚀 Starting development infrastructure..."
	docker-compose -f docker-compose.dev.yml up -d
	@echo "⏳ Waiting for services to be ready..."
	@sleep 10
	@echo "✅ Development infrastructure running"
	@echo "📊 Check status with: make dev.status"

dev.down:
	@echo "⏹️  Stopping development infrastructure..."
	docker-compose -f docker-compose.dev.yml down
	@echo "✅ Development infrastructure stopped"

dev.restart:
	@echo "🔄 Restarting development infrastructure..."
	docker-compose -f docker-compose.dev.yml restart
	@echo "✅ Development infrastructure restarted"

dev.logs:
	docker-compose -f docker-compose.dev.yml logs -f frankenphp

dev.status:
	@echo "📊 Development Infrastructure Status:"
	@echo ""
	docker-compose -f docker-compose.dev.yml ps
	@echo ""
	@echo "🔍 Supervisord Processes:"
	@docker-compose -f docker-compose.dev.yml exec frankenphp supervisorctl status || true
	@echo ""
	@echo "📈 Redis Stats:"
	@docker-compose -f docker-compose.dev.yml exec redis redis-cli INFO stats | grep -E "total_commands_processed|instantaneous_ops_per_sec" || true

dev.run:
	@if [ -z "$(version)" ]; then \
		if [ -z "$(test)" ]; then \
			time docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console benchmark:run --iterations=$(or $(iterations),1); \
		else \
			time docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console benchmark:run --test=$(test) --iterations=$(or $(iterations),1); \
		fi \
	else \
		time docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console benchmark:run --test=$(test) --iterations=$(or $(iterations),1) --php-version=$(version); \
	fi

# Production commands
prod.build:
	@echo "🏗️  Building production infrastructure..."
	docker-compose -f docker-compose.prod.yml build
	@echo "✅ Production build complete"

prod.up:
	@echo "🚀 Starting production infrastructure..."
	docker-compose -f docker-compose.prod.yml up -d
	@echo "⏳ Waiting for services to be ready..."
	@sleep 10
	@echo "✅ Production infrastructure running"
	@echo "📊 Check status with: make prod.status"

prod.down:
	@echo "⏹️  Stopping production infrastructure..."
	docker-compose -f docker-compose.prod.yml down
	@echo "✅ Production infrastructure stopped"

prod.restart:
	@echo "🔄 Restarting production infrastructure..."
	docker-compose -f docker-compose.prod.yml restart
	@echo "✅ Production infrastructure restarted"

prod.logs:
	docker-compose -f docker-compose.prod.yml logs -f frankenphp

prod.status:
	@echo "📊 Production Infrastructure Status:"
	@echo ""
	docker-compose -f docker-compose.prod.yml ps
	@echo ""
	@echo "🔍 Supervisord Processes:"
	@docker-compose -f docker-compose.prod.yml exec frankenphp supervisorctl status || true
	@echo ""
	@echo "📈 Redis Stats:"
	@docker-compose -f docker-compose.prod.yml exec redis redis-cli INFO stats | grep -E "total_commands_processed|instantaneous_ops_per_sec" || true

prod.run:
	@if [ -z "$(version)" ]; then \
		if [ -z "$(test)" ]; then \
			time docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console benchmark:run --iterations=$(or $(iterations),1); \
		else \
			time docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console benchmark:run --test=$(test) --iterations=$(or $(iterations),1); \
		fi \
	else \
		time docker-compose -f docker-compose.prod.yml exec frankenphp php bin/console benchmark:run --test=$(test) --iterations=$(or $(iterations),1) --php-version=$(version); \
	fi

# CI/CD commands (for GitHub Actions and similar)
ci.build:
	@echo "🏗️  Building CI infrastructure..."
	docker-compose -f docker-compose.ci.yml build
	@echo "✅ CI build complete"

ci.up:
	@echo "🚀 Starting CI infrastructure..."
	docker-compose -f docker-compose.ci.yml up -d
	@echo "⏳ Waiting for services to be healthy..."
	@sleep 15
	@echo "✅ CI infrastructure running"

ci.down:
	@echo "⏹️  Stopping CI infrastructure..."
	docker-compose -f docker-compose.ci.yml down -v
	@echo "✅ CI infrastructure stopped and cleaned"

ci.logs:
	docker-compose -f docker-compose.ci.yml logs -f frankenphp

ci.test:
	@echo "🧪 Running tests in CI environment..."
	docker-compose -f docker-compose.ci.yml exec -T frankenphp vendor/bin/phpunit --testdox

ci.quality:
	@echo "🔍 Running quality checks in CI environment..."
	@docker-compose -f docker-compose.ci.yml exec -T frankenphp vendor/bin/phpstan analyse --no-progress --error-format=github --memory-limit=512M
	@docker-compose -f docker-compose.ci.yml exec -T frankenphp vendor/bin/php-cs-fixer fix --dry-run --diff
	@docker-compose -f docker-compose.ci.yml exec -T frankenphp vendor/bin/phpmd src github rulesets.xml

# Benchmark command (uses dev environment by default)
# Usage examples:
#   make run test=Loop iterations=3
#   make run test=HashWithSha256 iterations=50 version=php84
#   make run iterations=10
# Note: If iterations not specified, command uses calibrated values from fixtures (default: 10)
run:
	@if [ -z "$(version)" ]; then \
		if [ -z "$(test)" ]; then \
			docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console benchmark:run $(if $(iterations),--iterations=$(iterations)); \
		else \
			docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console benchmark:run --test=$(test) $(if $(iterations),--iterations=$(iterations)); \
		fi \
	else \
		docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console benchmark:run --test=$(test) $(if $(iterations),--iterations=$(iterations)) --php-version=$(version); \
	fi

# Calibrate benchmark iterations based on target execution time
# Usage: make calibrate (all benchmarks)
#        make calibrate [benchmark=abs-with-abs] (specific benchmark)
#        make calibrate [target-time=500] (custom target time)
calibrate:
	@docker-compose -f docker-compose.dev.yml run --rm frankenphp \
		php bin/console benchmark:calibrate --all $(if $(benchmark),--benchmark=$(benchmark)) $(if $(target-time),--target-time=$(target-time))

# Calibrate with --force flag to update already configured benchmarks
# Usage: make calibrate-force (all benchmarks)
#        make calibrate-force [benchmark=abs-with-abs] (specific benchmark)
#        make calibrate-force [target-time=500] (custom target time)
calibrate-force:
	@docker-compose -f docker-compose.dev.yml run --rm frankenphp \
		php bin/console benchmark:calibrate --all --force $(if $(benchmark),--benchmark=$(benchmark)) $(if $(target-time),--target-time=$(target-time))

# Dry-run calibration with --force flag (preview changes without modifying fixtures)
# Usage: make calibrate-force-dry
#        make calibrate-force-dry [benchmark=abs-with-abs]
calibrate-force-dry:
	@docker-compose -f docker-compose.dev.yml run --rm frankenphp \
		php bin/console benchmark:calibrate --all --force --dry-run $(if $(benchmark),--benchmark=$(benchmark)) $(if $(target-time),--target-time=$(target-time))

# Load fixtures into database from YAML files
fixtures:
	@echo "🔄 Loading fixtures from fixtures/benchmarks/*.yaml..."
	@docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console doctrine:fixtures:load --no-interaction
	@echo "✅ Fixtures loaded successfully"

# Reset database (drop, create, migrate) - without fixtures
db.reset:
	@echo "🗑️  Dropping database..."
	@docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console d:d:d --force --if-exists
	@echo "📦 Creating database..."
	@docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console d:d:c
	@echo "🔄 Running migrations..."
	@docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console d:m:m --no-interaction
	@echo "✅ Database reset complete"

# Reset database and load fixtures (full refresh)
db.refresh: db.reset fixtures
	@echo "✅ Database refreshed with fixtures"

phpcsfixer:
	docker-compose -f docker-compose.dev.yml exec frankenphp vendor/bin/php-cs-fixer fix --dry-run --diff

phpcsfixer-fix:
	docker-compose -f docker-compose.dev.yml exec frankenphp vendor/bin/php-cs-fixer fix

phpstan:
	docker-compose -f docker-compose.dev.yml exec frankenphp vendor/bin/phpstan analyse --memory-limit=512M

phpmd:
	docker-compose -f docker-compose.dev.yml exec frankenphp vendor/bin/phpmd ./src ansi rulesets.xml

phparkitect:
	docker-compose -f docker-compose.dev.yml exec frankenphp vendor/bin/phparkitect check

rector:
	docker-compose -f docker-compose.dev.yml exec frankenphp vendor/bin/rector

test:
	docker-compose -f docker-compose.dev.yml exec frankenphp vendor/bin/phpunit

test-coverage:
	@echo "📊 Generating code coverage..."
	docker-compose -f docker-compose.dev.yml exec frankenphp phpdbg -qrr vendor/bin/phpunit --coverage-xml=var/coverage/coverage-xml --log-junit=var/coverage/junit.xml

infection:
	@echo "🧬 Running Infection mutation testing..."
	@echo "⚠️  This may take several minutes..."
	@echo "📊 Step 1/2: Generating code coverage with PHPUnit..."
	@docker-compose -f docker-compose.dev.yml exec frankenphp phpdbg -qrr vendor/bin/phpunit --coverage-xml=var/coverage/coverage-xml --log-junit=var/coverage/junit.xml
	@echo "🧬 Step 2/2: Running mutations..."
	docker-compose -f docker-compose.dev.yml exec frankenphp vendor/bin/infection --coverage=var/coverage --threads=4 --show-mutations --min-msi=80 --min-covered-msi=85

infection-report:
	@echo "🧬 Running Infection mutation testing (report only, no MSI threshold)..."
	@echo "📊 Step 1/2: Generating code coverage with PHPUnit..."
	@docker-compose -f docker-compose.dev.yml exec frankenphp phpdbg -qrr vendor/bin/phpunit --coverage-xml=var/coverage/coverage-xml --log-junit=var/coverage/junit.xml
	@echo "🧬 Step 2/2: Running mutations..."
	docker-compose -f docker-compose.dev.yml exec frankenphp vendor/bin/infection --coverage=var/coverage --threads=4 --show-mutations

quality: phpcsfixer-fix phpstan phpmd phparkitect

# Force refresh assets (CSS/JS) and invalidate browser cache
# Useful when CSS/JS changes are not reflected in the browser
# This command:
#   1. Compiles all assets (SCSS → CSS, etc.)
#   2. Deletes compiled assets to force new hash generation
#   3. Clears Symfony cache
#   4. Restarts the frankenphp container for a clean state
assets.refresh:
	@echo "🎨 Compiling assets..."
	@docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console asset-map:compile
	@echo "🗑️  Removing compiled assets to force hash regeneration..."
	@rm -rf public/assets
	@echo "🔄 Clearing Symfony cache..."
	@docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console cache:clear
	@echo "🔄 Restarting frankenphp container..."
	@docker-compose -f docker-compose.dev.yml restart frankenphp
	@echo "✅ Assets refreshed! New CSS/JS hashes generated."
	@echo "💡 Hard refresh your browser (Ctrl+Shift+R or Cmd+Shift+R) to see changes."

# Compile translations from YAML to optimized XLF format
# XLF format provides ~2-3x faster translation lookup performance vs YAML
# Run this after modifying translations/messages.*.yaml files
trans.compile:
	@echo "🌍 Compiling translations (YAML → XLF)..."
	@docker-compose -f docker-compose.dev.yml exec frankenphp php bin/console translation:extract --force fr
	@echo "✅ Translations compiled successfully"
	@echo "📊 Performance: XLF format provides 2-3x faster lookups than YAML"

# Update translations: extract new keys from templates and compile
trans.update: trans.compile
	@echo "✅ Translations updated and compiled"
