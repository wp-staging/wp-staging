<?php

use WPStaging\Tests\Page\Webdriver\Start;

class finishCest
{
    public function finishTests(WebdriverTester $I, Start $start)
    {
        system('chown -R www-data:www-data /var/www/single_tests');
        system('chown -R www-data:www-data /var/www/multi_tests');
        $I->loginAsAdmin();
        $start->deleteAllSites();

        $sites = $I->grabOptionFromDatabase('wpstg_existing_clones_beta');

        if ( ! empty($sites)) {
            throw new RuntimeException('Tests finished without deleting all sites.');
        }
    }
}
