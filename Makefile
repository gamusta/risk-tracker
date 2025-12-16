.PHONY: install start stop test test-unit test-functional test-all test-coverage db-reset db-reset-test logs

install:
	docker compose build
	docker compose run --rm php composer install
	docker compose run --rm -w /frontend node:20-alpine npm install

start:
	docker compose up -d

stop:
	docker compose down

# Tests unitaires uniquement
test-unit:
	php bin/phpunit tests/Unit/

# Tests fonctionnels uniquement
test-functional:
	php bin/console doctrine:database:drop --env=test --force --if-exists
	php bin/console doctrine:database:create --env=test
	php bin/console doctrine:migrations:migrate --env=test -n
	php bin/phpunit tests/Functional/

# Tous les tests
test-all: test-unit test-functional

# Tests avec coverage
test-coverage:
	XDEBUG_MODE=coverage php bin/phpunit --coverage-html coverage/

# Analyse statique + tests
test:
	@echo "→ Tests unitaires..."
	php bin/phpunit tests/Unit/
	@echo "\n→ Tests fonctionnels..."
	php bin/console doctrine:database:drop --env=test --force --if-exists
	php bin/console doctrine:database:create --env=test
	php bin/console doctrine:migrations:migrate --env=test -n
	php bin/phpunit tests/Functional/
	@echo "\n→ Analyse statique..."
	php vendor/bin/phpstan analyse src --level=5

# Reset DB dev
db-reset:
	php bin/console doctrine:database:drop --force --if-exists
	php bin/console doctrine:database:create
	php bin/console doctrine:migrations:migrate -n

# Reset DB test
db-reset-test:
	php bin/console doctrine:database:drop --env=test --force --if-exists
	php bin/console doctrine:database:create --env=test
	php bin/console doctrine:migrations:migrate --env=test -n

# Fixtures (si installées)
db-fixtures:
	php bin/console doctrine:fixtures:load -n

logs:
	docker compose logs -f

php-bash:
	docker compose exec php sh
