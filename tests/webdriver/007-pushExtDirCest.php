<?php

class pushingExternalDirectoryCest
{

    /**
     * Login to WordPress
     */
    public function _before(WebdriverTester $I)
    {
	    $I->loginAsAdmin(10, 3);
	    $I->amOnPage('/wp-admin/admin.php?page=wpstg_clone');
	    $I->waitForJqueryAjax(30);
    }

    /**
     * Check single clone settings
     *
     * @param WebdriverTester $I
     *
     * @env single
     */
    public function checkCloneSettingsSingle(WebdriverTester $I)
    {
	    $I->waitForText('Database: single_tests', 10);
        $I->see('Directory: /var/www/single_tests/customdir/');
    }

    /**
     * Check multisite multipath settings
     *
     * @param WebdriverTester $I
     *
     * @env multi
     */
    public function checkCloneSettingsMultipath(WebdriverTester $I)
    {
	    $I->waitForText('Database: multi_tests', 10);
        $I->see('Directory: /var/www/multi_tests/customdir/');
    }

    /**
     * Create Content to push
     * Reset the parent website and change the staging sites post title
     * @param WebdriverTester $I
     */
    public function createContent(WebdriverTester $I)
    {

        // Gutenberg editor used
        try {

            // Reset content of production site
            $I->amOnPage('/wp-admin/edit.php');
            $I->click('//*[@id="post-1"]/td[1]/strong/a');

            $I->click('body');

            /* Select the entire text with ctrl + a and delete it with BACKSPACE.
             * This is the only way how it works for WordPress Gutenberg editor as it is written in REACT.
             * In REACT the test appends a string to the text field instead replacing it when using fillField. 'clearField' is not working either.
             * E.g. Replace world with hello and you'll get 'hellworld' instead 'hello'
             * */
            $I->pressKey('#post-title-0', array('ctrl', 'a'));
            $I->pressKey('#post-title-0', \Facebook\WebDriver\WebDriverKeys::BACKSPACE);
            $I->fillField('#post-title-0', 'Reset Content');

            $I->click('.editor-post-publish-button');
	        $I->waitForJqueryAjax(30);
	        $I->wait(1);

            // Open staging site
            $I->amOnPage('/wp-admin/admin.php?page=wpstg_clone');
            $I->waitForElementVisible('#staging', 5);
            $I->click('//*[@id="staging"]/a[2]');


            // Get name of new window of staging site and switch to
            $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
                $handles = $webdriver->getWindowHandles();
                $lastWindow = end($handles);
                $this->lastWindow = $lastWindow;
                $webdriver->switchTo()->window($lastWindow);
            });

            $I->fillField(['name' => 'wpstg-username'], 'admin');
            $I->fillField(['name' => 'wpstg-pass'], 'password');
            $I->click('#wp-submit');
            $I->waitForText('Dashboard', 5);

            // Chenge content of staging site
            $I->amOnPage('/customdir/wp-admin/edit.php');
            $I->click('//*[@id="post-1"]/td[1]/strong/a');


            $I->Click('#post-title-0');
            $I->pressKey('#post-title-0', array('ctrl', 'a'));
            $I->pressKey('#post-title-0', \Facebook\WebDriver\WebDriverKeys::BACKSPACE);
            $I->fillField('#post-title-0', 'Pushed Content');
            $I->click('.editor-post-publish-button');
            $I->waitForJqueryAjax(10);
            $I->wait(1);
            // Legacy WordPress editor used
        } catch (Exception $e) {

	        // Throw: We use only Gutenberg for now.
	        throw $e;

            // Reset content of production site
            $I->amOnPage('/wp-admin/edit.php');
            $I->click('//*[@id="post-1"]/td[1]/strong/a');
            $I->fillField('#title', 'Reset Content');
            $I->click('#publish');

            // Open staging site
            $I->amOnPage('/wp-admin/admin.php?page=wpstg_clone');
            $I->waitForElementVisible('#staging', 5);
            $I->click('//*[@id="staging"]/a[2]');

            // Get name of new window of staging site and switch to
            $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
                $handles = $webdriver->getWindowHandles();
                $lastWindow = end($handles);
                $this->lastWindow = $lastWindow;
                $webdriver->switchTo()->window($lastWindow);
            });
            $I->fillField(['name' => 'wpstg-username'], 'admin');
            $I->fillField(['name' => 'wpstg-pass'], 'password');
            $I->click('#wp-submit');
            $I->waitForText('Dashboard', 2);
            // Change content of staging site
            $I->amOnPage('/customdir/wp-admin/edit.php');
            $I->click('//*[@id="post-1"]/td[1]/strong/a');
            $I->fillField('#title', 'Pushed Content');
            $I->click('#publish');
        }
    }

    /**
     * Push database and files for single and multisite env
     * All tables
     * All folder
     * Main Database
     * @param WebdriverTester $I
     */
    public function pushing(WebdriverTester $I)
    {
        $I->click('#staging a.wpstg-push-changes');
        $I->waitForElementVisible('.wpstg-tabs-wrapper', 2);
        $I->click('#wpstg-push-changes');
        $I->acceptPopup();
        $I->waitForText('Finished', 200, '.wpstg-loader');
        //$I->see('DB Rename: Has been finished successfully. Cleaning up...');
        $I->amOnPage('/');
        $I->see('Pushed Content');
    }
}
