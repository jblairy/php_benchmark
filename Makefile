.PHONY: up start run phpcsfixer phpcsfixer-fix phpstan quality phpmd phparkitect

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

db.reset:
	docker-compose run main php bin/console d:d:d --force; \
	docker-compose run main php bin/console d:d:c; \
	docker-compose run main php bin/console d:m:m; \

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
