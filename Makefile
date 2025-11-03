.PHONY: up start run fixtures db.reset db.refresh phpcsfixer phpcsfixer-fix phpstan quality phpmd phparkitect

up:
	docker-compose up -d --remove-orphans

start:
	docker-compose build

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
	docker-compose run --rm main vendor/bin/phpstan analyse

phpmd:
	docker-compose run --rm main vendor/bin/phpmd ./src ansi rulesets.xml

phparkitect:
	docker-compose run --rm main vendor/bin/phparkitect check

rector:
	docker-compose run --rm main vendor/bin/rector

quality: phpcsfixer-fix phpstan phpmd phparkitect
