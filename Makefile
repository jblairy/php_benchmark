.PHONY: up run phpcsfixer phpcsfixer-fix phpstan quality phpmd

up:
	docker-compose up -d --remove-orphans

#usage make run test=Loop iterations=3
run:
	docker-compose run --rm main php bin/console benchmark:run --test=$(test) --iterations=$(iterations)

phpcsfixer:
	docker-compose run --rm main vendor/bin/php-cs-fixer fix --dry-run --diff

phpcsfixer-fix:
	docker-compose run --rm main vendor/bin/php-cs-fixer fix

phpstan:
	docker-compose run --rm main vendor/bin/phpstan analyse

phpmd:
	docker-compose run --rm main vendor/bin/phpmd ./src ansi rulesets.xml

quality: phpcsfixer-fix phpstan phpmd
