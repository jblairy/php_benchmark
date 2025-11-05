.PHONY: up start run fixtures db.reset db.refresh phpcsfixer phpcsfixer-fix phpstan quality phpmd phparkitect infection assets.refresh trans.compile trans.update prod.up prod.build prod.down prod.restart prod.logs prod.status prod.run

up:
	docker-compose up -d --remove-orphans

start:
	docker-compose build

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

# New refactored benchmark command
# Usage examples:
#   make run test=Loop iterations=3
#   make run test=HashWithSha256 iterations=50 version=php84
#   make run iterations=10
run:
	@if [ -z "$(version)" ]; then \
		if [ -z "$(test)" ]; then \
			docker-compose run --rm main php bin/console benchmark:run --iterations=$(or $(iterations),1); \
		else \
			docker-compose run --rm main php bin/console benchmark:run --test=$(test) --iterations=$(or $(iterations),1); \
		fi \
	else \
		docker-compose run --rm main php bin/console benchmark:run --test=$(test) --iterations=$(or $(iterations),1) --php-version=$(version); \
	fi

# Load fixtures into database from YAML files
fixtures:
	@echo "🔄 Loading fixtures from fixtures/benchmarks/*.yaml..."
	@docker-compose exec main php bin/console doctrine:fixtures:load --no-interaction
	@echo "✅ Fixtures loaded successfully"

# Reset database (drop, create, migrate) - without fixtures
db.reset:
	@echo "🗑️  Dropping database..."
	@docker-compose run --rm main php bin/console d:d:d --force --if-exists
	@echo "📦 Creating database..."
	@docker-compose run --rm main php bin/console d:d:c
	@echo "🔄 Running migrations..."
	@docker-compose run --rm main php bin/console d:m:m --no-interaction
	@echo "✅ Database reset complete"

# Reset database and load fixtures (full refresh)
db.refresh: db.reset fixtures
	@echo "✅ Database refreshed with fixtures"

phpcsfixer:
	docker-compose run --rm main vendor/bin/php-cs-fixer fix --dry-run --diff

phpcsfixer-fix:
	docker-compose run --rm main vendor/bin/php-cs-fixer fix

phpstan:
	docker-compose run --rm main vendor/bin/phpstan analyse --memory-limit=512M

phpmd:
	docker-compose run --rm main vendor/bin/phpmd ./src ansi rulesets.xml

phparkitect:
	docker-compose run --rm main vendor/bin/phparkitect check

rector:
	docker-compose run --rm main vendor/bin/rector

test:
	docker-compose run --rm main vendor/bin/phpunit

test-coverage:
	@echo "📊 Generating code coverage..."
	docker-compose run --rm main phpdbg -qrr vendor/bin/phpunit --coverage-xml=var/coverage/coverage-xml --log-junit=var/coverage/junit.xml

infection:
	@echo "🧬 Running Infection mutation testing..."
	@echo "⚠️  This may take several minutes..."
	@echo "📊 Step 1/2: Generating code coverage with PHPUnit..."
	@docker-compose run --rm main phpdbg -qrr vendor/bin/phpunit --coverage-xml=var/coverage/coverage-xml --log-junit=var/coverage/junit.xml
	@echo "🧬 Step 2/2: Running mutations..."
	docker-compose run --rm main vendor/bin/infection --coverage=var/coverage --threads=4 --show-mutations --min-msi=80 --min-covered-msi=85

infection-report:
	@echo "🧬 Running Infection mutation testing (report only, no MSI threshold)..."
	@echo "📊 Step 1/2: Generating code coverage with PHPUnit..."
	@docker-compose run --rm main phpdbg -qrr vendor/bin/phpunit --coverage-xml=var/coverage/coverage-xml --log-junit=var/coverage/junit.xml
	@echo "🧬 Step 2/2: Running mutations..."
	docker-compose run --rm main vendor/bin/infection --coverage=var/coverage --threads=4 --show-mutations

quality: phpcsfixer-fix phpstan phpmd phparkitect

# Force refresh assets (CSS/JS) and invalidate browser cache
# Useful when CSS/JS changes are not reflected in the browser
# This command:
#   1. Compiles all assets (SCSS → CSS, etc.)
#   2. Deletes compiled assets to force new hash generation
#   3. Clears Symfony cache
#   4. Restarts the main container for a clean state
assets.refresh:
	@echo "🎨 Compiling assets..."
	@docker-compose exec main php bin/console asset-map:compile
	@echo "🗑️  Removing compiled assets to force hash regeneration..."
	@rm -rf public/assets
	@echo "🔄 Clearing Symfony cache..."
	@docker-compose exec main php bin/console cache:clear
	@echo "🔄 Restarting main container..."
	@docker-compose restart main
	@echo "✅ Assets refreshed! New CSS/JS hashes generated."
	@echo "💡 Hard refresh your browser (Ctrl+Shift+R or Cmd+Shift+R) to see changes."

# Compile translations from YAML to optimized XLF format
# XLF format provides ~2-3x faster translation lookup performance vs YAML
# Run this after modifying translations/messages.*.yaml files
trans.compile:
	@echo "🌍 Compiling translations (YAML → XLF)..."
	@docker-compose run --rm main php bin/console translation:extract --force fr
	@echo "✅ Translations compiled successfully"
	@echo "📊 Performance: XLF format provides 2-3x faster lookups than YAML"

# Update translations: extract new keys from templates and compile
trans.update: trans.compile
	@echo "✅ Translations updated and compiled"
