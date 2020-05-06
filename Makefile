start:
	php -S localhost:8000 -t public public/index.php
lint:
	composer run-script phpcs -- --standard=ruleset.xml public