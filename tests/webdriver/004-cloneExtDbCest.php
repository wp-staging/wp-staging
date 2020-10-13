<?php

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

    /**
     * Delete staging site "staging"
     * @param WebdriverTester $ID
     */
    public function deleteSite(WebdriverTester $I)
    {
        try {
            $I->see('staging', '#staging');
            $I->click('#staging .wpstg-remove-clone');
            $I->waitForElementVisible("#wpstg-remove-clone", 7);
            $I->wait(1);
            $I->click('#wpstg-remove-clone');
            $I->waitForElementNotVisible('#wpstg-removing-clone', 60);
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Clone to external database
     * Default prefix
     * Default Hostname
     * @param WebdriverTester $I
     */
    public function cloneExternal(WebdriverTester $I)
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
        $I->click('#wpstg-start-cloning');
        $I->cantSee('wpstg-error-details');
        $I->waitForElementVisible('#wpstg-clone-url', 350);
        $I->see('Congratulations');
	    $I->scrollTo('#wpstg-clone-url', 0, -50);
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
        $I->fillField(['name' => 'wpstg-username'], 'admin');
        $I->fillField(['name' => 'wpstg-pass'], 'password');
        $I->click('#wp-submit');
        $I->see('Dashboard');
        $I->amOnPage('/staging/wp-admin/admin.php?page=wpstg_clone');
        $I->see('This staging site can be pushed and modified with WP Staging Pro plugin', '.wpstg-notice-alert');
    }

}
