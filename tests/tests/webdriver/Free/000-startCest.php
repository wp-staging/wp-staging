<?php

use WPStaging\Tests\Page\Webdriver\Start;

class startCest
{
    public function startTests(WebdriverTester $I)
    {
        $I->checkEnvFlag();
        $I->loginAsAdmin(10, 3);
        $I->amOnPluginsPage();
        $I->canSeePluginActivated('wp-staging');
    }

    /**
     * @env multi
     */
    public function assertMultiSiteNotSupportedInFreeVersion(WebdriverTester $I, Start $start) {
        $I->loginAsAdmin(10, 3);
        $start->goHere();
        $I->see('WordPress Multisite is not supported! Upgrade to WP Staging Pro');
    }


    /**
     * @env single
     */
    public function deleteAllSites(Start $start) {
        $start->deleteAllSites();
    }
}
