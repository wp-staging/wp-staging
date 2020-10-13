<?php

use WPStaging\Tests\Page\Webdriver\Start;

class finishCest
{
    /**
     * @env single
     */
    public function finishTests(WebdriverTester $I, Start $start)
    {
        if ( ! empty($sites)) {
            shell_exec('chown -R www-data:www-data /var/www/single_tests');
            shell_exec('chown -R www-data:www-data /var/www/multi_tests');

            $I->loginAsAdmin(10, 3);
            
            $start->deleteAllSites();

            if ( ! empty($I->grabOptionFromDatabase('wpstg_existing_clones_beta'))) {
                throw new RuntimeException('Tests finished without deleting all sites.');
            }
        }
    }
}
