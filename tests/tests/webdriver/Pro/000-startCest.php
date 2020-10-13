<?php

use WPStaging\Tests\Page\Webdriver\Start;

class startCest
{
    public function startTests(WebdriverTester $I, Start $start)
    {
        $I->checkEnvFlag();
        $I->loginAsAdmin(10, 3);
        $I->amOnPluginsPage();
        $I->canSeePluginActivated('wp-staging-pro');
        $start->deleteAllSites();
    }
}
