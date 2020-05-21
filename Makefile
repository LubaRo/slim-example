install:
	composer install
start:
	php -S localhost:8000 -t public public/index.php
lint:
	composer run-script phpcs -- --standard=PSR12,ruleset.xml public helpers src
