p:
cd P:\wp-staging.com\github\wp-staging-pro
php codecept.phar run --env single --steps
php codecept.phar run --env single --steps acceptance 001-cloneCest.php:cloneSite

php codecept.phar run --env single --steps acceptance cloneExternalCest.php
