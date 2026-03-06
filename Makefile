qa: phpstan rector-check cs-check phpunit
qa-fix: phpstan rector-fix cs-fix phpunit

DOCKER=docker compose run --rm --workdir=/var/www workspace

phpstan:
	$(DOCKER) vendor/bin/phpstan analyse --memory-limit=1G

rector-check:
	$(DOCKER) vendor/bin/rector process --dry-run

cs-check:
	$(DOCKER) vendor/bin/php-cs-fixer fix --dry-run --diff

phpunit:
	$(DOCKER) vendor/bin/phpunit

coverage:
	$(DOCKER) vendor/bin/phpunit --coverage-xml .phpunit-coverage --testsuite Unit --bootstrap vendor/autoload.php

rector-fix:
	$(DOCKER) vendor/bin/rector process

cs-fix:
	$(DOCKER) vendor/bin/php-cs-fixer fix

phony: qa qa-fix phpstan rector-check cs-check phpunit rector-fix cs-fix