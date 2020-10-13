<?php

use WPStaging\Tests\Page\Webdriver\Login;
use WPStaging\Tests\Page\Webdriver\Start;

class cloningCest
{
    /**
     * @env single
     */
    public function cloneSite(WebdriverTester $I, Login $login, Start $start)
    {
        // Create new Staging site
        $I->loginAsAdmin();
        $start->goHere();
        $I->waitForElementClickable('#wpstg-new-clone');
        $I->click('#wpstg-new-clone');
        $I->waitForElement('#wpstg-new-clone-id', 5);
        $I->fillField('#wpstg-new-clone-id', 'staging');
        $I->waitForElement('#wpstg-start-cloning', 5);
        $I->click('#wpstg-start-cloning');
        $I->waitForElementVisible('#wpstg-clone-url', 350);
        $I->see('Congratulations');
        $I->click('#wpstg-clone-url');
        $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
            $handles = $webdriver->getWindowHandles();
            $lastWindow = end($handles);
            $webdriver->switchTo()->window($lastWindow);
        });
        $login->loginOnStagingSite('staging');
        $I->amOnPage('/staging/wp-admin/admin.php?page=wpstg_clone');
        $I->see('This staging site can be pushed and modified with WP Staging Pro plugin', '.wpstg-notice-alert');
    }
}
