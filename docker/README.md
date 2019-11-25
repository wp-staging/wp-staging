### IMPORTANT
Files in `docker/install/*` must be executable within host (your own) FS (file system) 
as permissions will be copied as it is to given destination in container FS.

### INSTALLATION
* Copy `.env.dist` as `.env`
* No need to make any changes other than adding your **license key** to `LICENSE_KEY` in `.env` file
* Make sure to add given IP address in `DOCKER_GATEWAY_IP` value, in `.env` file to your hosts file. Such as;
```
172.200.0.1 single.wp-staging.local
172.200.0.1 multi.wp-staging.local
``` 
* Run `make init` for the first time installation.

**WARNING** If you have changed `HOST_SINGLE` or `HOST_MULTI` values in `.env` file, make sure to reflect changes 
to your hosts file.

### RUNNING
* If you already installed before, just run `make up` or `make start` to start the project

### TROUBLE SHOOTING
If you get `ERROR 2002 (HY000): Can't connect to MySQL server on 'database' (115)` error, this is because of two things;
* The database server didn't kick-in on your host. If the host is slow, you might need to increase value of 
`WAIT_SERVICES_IN_SECONDS` in `.env` file.
* If you see the service is up & running but container logs spits out something along the lines of 
`aborrted connection to db unconnected user unauthenticated` then it is likely due to [this bug](https://github.com/mysql-net/MySqlConnector/issues/290).

My `.env` changes are not reflected;
* You need to restart containers every time you make a change in your `.env` file. Containers read this information upon initialization. 
They are not changed dynamically.
