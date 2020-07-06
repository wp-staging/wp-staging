.PHONY: up stop down build install test start restart init

include .env
export $(shell sed 's/=.*//' .env)

up:
	docker-compose up -d

stop:
	docker-compose stop

down:
	docker-compose down

build:
	docker-compose build

install:
	docker exec --user root -i $${COMPOSE_PROJECT_NAME}_php-fpm_1 bash -c "install"

test:
	php -d max_execution_time=60 ./src/vendor/bin/phpunit -c ./
#	php -d max_execution_time=60 ./src/vendor/bin/phpunit -c ./ --debug

dist:
	rm -f ./wp-staging.zip
	docker-compose run --rm composer install --no-dev
	cp -a ./src/. ./wp-staging/
	sed -i "s/{{version}}/$(VERSION)/g" ./wp-staging/wp-staging.php
	sed -i "s/('WPSTG_VERSION',.*'.*')/('WPSTG_VERSION', '$(VERSION)')/g" ./wp-staging/wp-staging.php
	sed -i "s/{{version}}/$(VERSION)/g" ./wp-staging/readme.txt
	rm -rf ./wp-staging/var/*
	rm -rf ./wp-staging/var/
	zip -r wp-staging.zip ./wp-staging
	rm -rf ./wp-staging



# Combinations & Aliases
start: up
restart: down up
init: build up install
reset: down stop
	rm -rf ./var/www/*
	touch ./var/www/.gitkeep
	docker volume rm wp-staging_database
	make up
	sleep $${WAIT_SERVICES_IN_SECONDS}
	make install
	sudo chown $(USER):$(USER) ./var -R
test_up:
	./vendor/bin/chromedriver --url-base=/wd/hub /dev/null 2>&1 &
test_single:
	php vendor/bin/codecept run --debug --xml acceptance --env single --steps
test_multi:
	php vendor/bin/codecept run acceptance --env multisite --steps
test_acceptance:
	#php vendor/bin/codecept run acceptance 001-cloneCest.php --env single --steps
	#php vendor/bin/codecept run acceptance 002-pushCest.php --env single --steps
	#php vendor/bin/codecept run acceptance 003-updatingCest.php --env single --steps
	#php vendor/bin/codecept run acceptance 004-cloneExtDbCest.php --env single --steps
	#php vendor/bin/codecept run acceptance 005-pushExtDbCest.php --env single --steps
	#php vendor/bin/codecept run acceptance 006-cloneExtDirCest.php --env single --steps
	#php vendor/bin/codecept run acceptance 007-pushExtDirCest.php --env single --steps

	#php vendor/bin/codecept run acceptance 001-cloneCest.php --env multisite --steps
	#php vendor/bin/codecept run --debug acceptance 002-pushCest.php --env multisite --steps
	#php vendor/bin/codecept run acceptance 003-updatingCest.php --env multisite --steps
	#php vendor/bin/codecept run acceptance 004-cloneExtDbCest.php --env multisite --steps
	#php vendor/bin/codecept run acceptance 005-pushExtDbCest.php --env multisite --steps
	#php vendor/bin/codecept run acceptance 006-cloneExtDirCest.php --env multisite --steps
	#php vendor/bin/codecept run acceptance 007-pushExtDirCest.php --env multisite --steps
