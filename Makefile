.PHONY: up stop down build install start restart init

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
	docker exec --user root -it $${COMPOSE_PROJECT_NAME}_php-fpm_1 bash -c "install"



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
test_up: # Tests require selenium server running
	java -jar selenium-server-standalone-3.141.5.jar
test_single:
	php codecept.phar run --env single --steps
test_multi:
	php codecept.phar run --env multisite --steps
test_acceptance:
	#php codecept.phar run acceptance 001-cloneCest.php --env single --steps
	#php codecept.phar run acceptance 003-updatingCest.php --env single --steps