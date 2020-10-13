<?php

use WPStaging\Tests\Page\Webdriver\Login;
use WPStaging\Tests\Page\Webdriver\Start;

class cloneExternalDirCest
{

    private $lastWindow = null;

    /**
     * Login to WordPress
     */
    public function _before(WebdriverTester $I, Start $start)
    {
	    $I->loginAsAdmin(10, 3);
    }

    /**
     * Clone to a custom directory
     * Default prefix
     * Default Hostname
     * @param WebdriverTester $I
     */
    public function cloneCustomDir(WebdriverTester $I, Start $start, Login $login)
    {
        $start->deleteAllSites();
        $start->goHere();
	    $I->waitForJqueryAjax(30);
	    $I->wait(1);
        $I->see('Create New Staging Site');
        $I->click('#wpstg-new-clone');
        $I->waitForElement('#wpstg-new-clone-id', 5);

        // Open advanced settings pull down
        $I->click('//*[@id="wpstg-workflow"]/div/a[3]/span/input');

        // Grab default data of hostname and destination dir before filling the staging name as it would change these vars
        $dir = rtrim( $I->grabAttributeFrom('#wpstg_clone_dir', 'placeholder'), '/' ) . '/customdir';
        $host = rtrim( $I->grabAttributeFrom('#wpstg_clone_hostname', 'placeholder'), '/' ) . '/customdir';

        // Make sure folder does not exist
        if (file_exists($dir)) {
            /*
             * Prevent tests failing because folder already exists.
             * Todo: Further debug why this happens_sometimes.
             */
            system('rm -rf /var/www/single_tests/customdir');
            system('rm -rf /var/www/multi_tests/customdir');
        }

        // Enter staging site name
        $I->fillField('#wpstg-new-clone-id', 'staging');

        // Enter settings
        $I->fillField('#wpstg_clone_dir', $dir);
        $I->fillField('#wpstg_clone_hostname', $host);

        // Start cloning
        $I->click('#wpstg-start-cloning');
        $I->cantSee('wpstg-error-details');
        $I->waitForElementVisible('#wpstg-clone-url', 350);
        $I->see('Congratulations');

        // Verify Clone
	    $I->scrollTo('#wpstg-clone-url', 0, -50);
        $I->click('#wpstg-clone-url');

        // Get name of new window of staging site
        $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
            $handles = $webdriver->getWindowHandles();
            $lastWindow = end($handles);
            $this->lastWindow = $lastWindow;
            $webdriver->switchTo()->window($lastWindow);
        });
        // Switch to clone
        $I->switchToWindow($this->lastWindow);
        $login->loginOnStagingSite('customdir');
        $I->amOnPage('/customdir/wp-admin/admin.php?page=wpstg_clone');
        $I->see('This staging site can be pushed and modified with WP Staging Pro plugin', '.wpstg-notice-alert');
    }

}
