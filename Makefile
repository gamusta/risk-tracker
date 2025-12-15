.PHONY: install start stop test db-reset logs

install:
	docker compose build
	docker compose run --rm php composer install
	docker compose run --rm -w /frontend node:20-alpine npm install

start:
	docker compose up -d

stop:
	docker compose down

test:
	docker compose exec php bin/phpunit
	docker compose exec php vendor/bin/phpstan analyse src --level=5

db-reset:
	docker compose exec php bin/console doctrine:database:drop --force --if-exists
	docker compose exec php bin/console doctrine:database:create
	docker compose exec php bin/console doctrine:migrations:migrate -n

logs:
	docker compose logs -f

php-bash:
	docker compose exec php sh
