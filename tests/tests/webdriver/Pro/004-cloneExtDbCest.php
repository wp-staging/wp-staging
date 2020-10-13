<?php

use WPStaging\Tests\Page\Webdriver\Login;
use WPStaging\Tests\Page\Webdriver\Start;

class cloneExternalDatabaseCest
{

    private $lastWindow = null;

    /**
     * Login to WordPress
     */
    public function _before(WebdriverTester $I)
    {
	    $I->loginAsAdmin(10, 3);
	    $I->amOnPage('/wp-admin/admin.php?page=wpstg_clone');
    }

    public function deleteSite(WebdriverTester $I, Start $start)
    {
        $start->deleteAllSites();
    }

    /**
     * Clone to external database
     * Default prefix
     * Default Hostname
     * @param WebdriverTester $I
     */
    public function cloneExternal(WebdriverTester $I, Login $login)
    {
	    $I->waitForJqueryAjax(30);
        $I->retrySee('Create New Staging Site');
        $I->click('#wpstg-new-clone');
        $I->waitForElement('#wpstg-new-clone-id', 5);
        $I->fillField('#wpstg-new-clone-id', 'staging');
        $I->click('//*[@id="wpstg-workflow"]/div/a[3]/span/input');
        $I->retryClick('#wpstg-ext-db');
        $I->retryFillField('#wpstg_db_server', 'database');
        $I->fillField('#wpstg_db_username', 'admin');
        $I->fillField('#wpstg_db_password', 'password');
        $I->fillField('#wpstg_db_database', 'external');
        $I->fillField('#wpstg_db_prefix', 'wp_');
        $I->click('#wpstg-start-cloning');
        $I->cantSee('wpstg-error-details');
        $I->waitForElementVisible('#wpstg-clone-url', 350);
        $I->see('Congratulations');
	    //$I->scrollTo('#wpstg-clone-url', 0, -50);
        $I->click('#wpstg-clone-url');
        // Get name of new window of staging site
        $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
            $handles = $webdriver->getWindowHandles();
            $lastWindow = end($handles);
            $this->lastWindow = $lastWindow;
            $webdriver->switchTo()->window($lastWindow);
        });
        // Switch to staging site
        $I->switchToWindow($this->lastWindow);
        $login->loginOnStagingSite('staging');
        $I->amOnPage('/staging/wp-admin/admin.php?page=wpstg_clone');
        $I->waitForText('This staging site can be pushed and modified with WP Staging Pro plugin');
    }

}
