.PHONY: install start stop test test-unit test-functional test-all test-coverage cs cs-fix phpstan quality db-reset db-reset-test logs

# Alias
PHP = docker compose exec php
CONSOLE = $(PHP) bin/console
PHPUNIT = $(PHP) bin/phpunit
COMPOSER = docker compose run --rm php composer

install:
	docker compose build
	$(COMPOSER) install
	docker compose run --rm -w /frontend node:20-alpine npm install

start:
	docker compose up -d

stop:
	docker compose down

# Tests unitaires uniquement
test-unit:
	$(PHPUNIT) tests/Unit/

# Tests fonctionnels uniquement
test-functional:
	$(CONSOLE) doctrine:database:drop --env=test --force --if-exists
	$(CONSOLE) doctrine:database:create --env=test
	$(CONSOLE) doctrine:migrations:migrate --env=test -n
	$(PHPUNIT) tests/Functional/

# Tous les tests
test-all: test-unit test-functional

# Tests avec coverage
test-coverage:
	$(PHP) sh -c "XDEBUG_MODE=coverage bin/phpunit --coverage-html coverage/"

# Code style check
cs:
	$(PHP) vendor/bin/phpcs src tests || [ $$? -eq 2 ]

# Code style auto-fix
cs-fix:
	$(PHP) vendor/bin/phpcbf src tests || true

# Static analysis
phpstan:
	$(PHP) vendor/bin/phpstan analyse --memory-limit=256M

# Quality: CS + PHPStan
quality: cs phpstan
	@echo "\n✅ Quality checks passed!"

# Full test suite
test:
	@echo "→ Code style..."
	$(PHP) vendor/bin/phpcs src tests
	@echo "\n→ Static analysis..."
	$(PHP) vendor/bin/phpstan analyse --memory-limit=256M
	@echo "\n→ Tests unitaires..."
	$(PHPUNIT) tests/Unit/
	@echo "\n→ Tests fonctionnels..."
	$(CONSOLE) doctrine:database:drop --env=test --force --if-exists
	$(CONSOLE) doctrine:database:create --env=test
	$(CONSOLE) doctrine:migrations:migrate --env=test -n
	$(PHPUNIT) tests/Functional/

# Reset DB dev
db-reset:
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create
	$(CONSOLE) doctrine:migrations:migrate -n

# Reset DB test
db-reset-test:
	$(CONSOLE) doctrine:database:drop --env=test --force --if-exists
	$(CONSOLE) doctrine:database:create --env=test
	$(CONSOLE) doctrine:migrations:migrate --env=test -n

# Fixtures
db-fixtures:
	$(CONSOLE) doctrine:fixtures:load -n

logs:
	docker compose logs -f

php-bash:
	docker compose exec php sh
