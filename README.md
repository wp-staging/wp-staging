![PHPUnit](https://github.com/WP-Staging/wp-staging/workflows/PHPUnit/badge.svg)

### Welcome to the WP Staging repository

## Note ##

This is the latest developer version of WP-Staging for WordPress. 

## Quick Start ##
### Requirements
- Docker

2. Clone the GitHub master branch: `https://github.com/WP-Staging/wp-staging-pro.git`
3. Copy `.env.dist` as `.env`
4. Run `make init` for the first time installation. Run `make up` later. 

Run `make init` will create docker containers with NGINX, mariaDB, PHP5.3. 
It will download latest WordPress version and will create a single and a network activated WordPress site
You can access them via `single.wp-staging.local` and `multi.wp-staging.local`  

## Credentials 
####WordPress admin user

U: admin
P: password

####Database

U: admin
P: Password

Mysql Root Password: 123456


## Executing Acceptance Tests

### Requirements
- php-cli, php-curl, php-mbstring, php-xml

1. Start the Selenium server with `make test_up`
2. Run `make test_single` or `make test_multi` (Check `Makefile` for more options)

## Installation ##

* Copy `.env.dist` as `.env`
* Make sure to add given IP address in `DOCKER_GATEWAY_IP` value, in `.env` file to your hosts file. Such as;
```
172.199.0.1 single.wp-staging-free.local
172.199.0.1 multi.wp-staging-free.local
``` 

Files in `docker/install/*` must be executable within host (your own) FS (file system) 
as permissions will be copied as it is to given destination in container FS.

* Run `make init` for the first time installation.

**WARNING** If you have changed `HOST_SINGLE` or `HOST_MULTI` values in `.env` file, make sure to reflect changes 
to your hosts file.

## Running
* If you already installed before, just run `make up` or `make start` to start the project

## Trouble Shooting
If you get `ERROR 2002 (HY000): Can't connect to MySQL server on 'database' (115)` error, this is because of two things;
* The database server didn't kick-in on your host. If the host is slow, you might need to increase value of 
`WAIT_SERVICES_IN_SECONDS` in `.env` file.
* If you see the service is up & running but container logs spits out something along the lines of 
`aborted connection to db unconnected user unauthenticated` then it is likely due to [this bug](https://github.com/mysql-net/MySqlConnector/issues/290).

My `.env` changes are not reflected;
* You need to restart containers every time you make a change in your `.env` file. Containers read this information upon initialization. 
They are not changed dynamically.

You can run `make reset`


## Bugs ##
If you find an issue, let us know [here](https://github.com/WP-Staging/wp-staging/issues?state=open)!

## Support ##
This is a developer's portal for WP-Staging

## Contributions ##
Anyone is welcome to contribute to WP-Staging. Please read the [guidelines for contributing](https://github.com/rene-hermenau/wp-staging/blob/master/CONTRIBUTING.md) to this repository.

There are various ways you can contribute:

1. Raise an [Issue](https://github.com/wp-staging/wp-staging/issues) on GitHub
2. Send us a Pull Request with your bug fixes and/or new features
3. Translate WP-Staging into different languages
4. Provide feedback and suggestions on [enhancements](https://github.com/WP-Staging/wp-staging/issues?direction=desc&labels=Enhancement&page=1&sort=created&state=open)
