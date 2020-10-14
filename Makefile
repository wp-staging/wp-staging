.PHONY: up stop down build install webdriver webdriver_single_free webdriver_multi_free webdriver_single_pro webdriver_multi_pro webdriver_single_test install_ci enter phpstan phpcs tag_version dist_pro dist_basic start restart init init_ci reset

include .env
export $(shell sed 's/=.*//' .env)

up:
	docker-compose up -d --scale chrome=2 --remove-orphans

stop:
	docker-compose stop

down:
	docker-compose down

build:
	docker-compose build

install:
	docker exec --user root -i $${COMPOSE_PROJECT_NAME}_php-fpm_1 bash -c "install"

# Run webdriver tests in parallel. Usage: make webdriver_parallel_pro -j 2
webdriver_parallel_pro: webdriver_single_pro webdriver_multi_pro

# Run webdriver tests in parallel. Usage: make webdriver_parallel_free -j 2
webdriver_parallel_free: webdriver_single_free webdriver_multi_free

# Run webdriver tests for single site for the Free version of WPSTAGING
webdriver_single_free:
	docker exec --user www-data -i $${COMPOSE_PROJECT_NAME}_php-fpm_1 bash -c "cd /var/www/tests && ./vendor/bin/codecept run webdriver --env single --fail-fast Free"

# Run webdriver tests for multi site for the Free version of WPSTAGING
webdriver_multi_free:
	docker exec --user www-data -i $${COMPOSE_PROJECT_NAME}_php-fpm_1 bash -c "cd /var/www/tests && ./vendor/bin/codecept run webdriver --env multi --fail-fast Free"

# Run webdriver tests for single site for the PRO version of WPSTAGING
webdriver_single_pro:
	docker exec --user www-data -i $${COMPOSE_PROJECT_NAME}_php-fpm_1 bash -c "cd /var/www/tests && ./vendor/bin/codecept run webdriver --env single --fail-fast Pro"

# Run webdriver tests for multi site for the PRO version of WPSTAGING
webdriver_multi_pro:
	docker exec --user www-data -i $${COMPOSE_PROJECT_NAME}_php-fpm_1 bash -c "cd /var/www/tests && ./vendor/bin/codecept run webdriver --env multi --fail-fast Pro"

# Run a webdriver for a particular class. Demo!
webdriver_single_test:
	docker exec --user www-data -i $${COMPOSE_PROJECT_NAME}_php-fpm_1 bash -c "cd /var/www/tests && ./vendor/bin/codecept run webdriver --env multi --fail-fast 004-cloneExtDbCest.php"

install_ci:
	docker exec --user root -i $${COMPOSE_PROJECT_NAME}_php-fpm_1 bash -c "install tests"

enter:
	docker exec -it $${COMPOSE_PROJECT_NAME}_php-fpm_1 bash

# Runs PHPStan using "phpstan.dist.neon" configuration
phpstan:
	docker exec --user www-data -i $${COMPOSE_PROJECT_NAME}_php-fpm_1 bash -c "cd /var/www/tests && php -d memory_limit=3G /var/www/tests/vendor/bin/phpstan analyse -c phpstan.dist.neon"

# Runs PHPCS
phpcs:
	docker exec --user www-data -i $${COMPOSE_PROJECT_NAME}_php-fpm_1 bash -c "cd /var/www/tests && php -d memory_limit=3G /var/www/tests/vendor/bin/phpcs"

# Builds the distributable Free version of the plugin based on src.
# Replaces {{wpstgStaticVersion}} with given version number on-the-fly, only in the generated code.
dist_basic:
	@[ "${VERSION}" ] || ( echo "VERSION is not set. Usage: make dist_basic VERSION=1.2.3"; exit 1 )
	# Dist folder cleanup
	rm -rf ./dist/wp-staging/
	rm -rf ./dist/wp-staging-pro/
	rm -rf ./dist/wp-staging.zip
	# Skip deleting wp-staging-pro.zip

	# Convert development version into distributable version
	mkdir -p ./dist/wp-staging/
	cp -a ./src/. ./dist/wp-staging/

	# Plugin entry-file and readme
	rm -f ./dist/wp-staging/wp-staging-pro.php
	rm -f ./dist/wp-staging/readme.txt
	cp -a ./dist/wp-staging/BASIC-DONT-INCLUDE/wp-staging.php ./dist/wp-staging/wp-staging.php
	cp -a ./dist/wp-staging/BASIC-DONT-INCLUDE/readme.txt ./dist/wp-staging/readme.txt

	# Text replacements
	sed -i "s/wpstgpro/wpstgfree/g" ./dist/wp-staging/composer.json
	sed -i "s/{{wpstgStaticVersion}}/$(VERSION)/g" ./dist/wp-staging/wp-staging.php
	sed -i "s/{{wpstgStaticVersion}}/$(VERSION)/g" ./dist/wp-staging/readme.txt
	sed -i "s/('WPSTG_VERSION',.*'.*')/('WPSTG_VERSION', '$(VERSION)')/g" ./dist/wp-staging/constants.php

	# Pro code cleanup and general cleanup
	rm -rf ./dist/wp-staging/var/
	rm -rf ./dist/wp-staging/vendor/
	rm -rf ./dist/wp-staging/BASIC-DONT-INCLUDE/
	rm -rf ./dist/wp-staging/Pro/
	rm -rf ./dist/wp-staging/Backend/Pro/

    # Composer and autoloader
	composer install -d ./dist/wp-staging --no-dev -o
	rm -f ./dist/wp-staging/composer.json
	rm -f ./dist/wp-staging/composer.lock

	# Make distributable .zip file
	cd dist && zip -qr ./wp-staging.zip ./wp-staging

	# Safety mechanism: Show in the terminal which files are not version-tagged.
	grep -rl {{wpstgFreeVersion}} --include \*.php ./dist/wp-staging || :
	grep -rl {{wpstgProVersion}} --include \*.php ./dist/wp-staging || :
	grep -rl {{wpstgStaticVersion}} --include \*.php ./dist/wp-staging || :

# Combinations & Aliases
start: up
restart: down up
init: build up install
init_ci: build up install_ci
reset: down stop
	rm -rf ./var/www/*
	touch ./var/www/.gitkeep
	docker volume rm wp-staging-pro_database
	make up
	make install
	sudo chown $(USER):$(USER) ./var -R
