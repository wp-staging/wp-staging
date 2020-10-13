<?php

use WPStaging\Tests\Page\Webdriver\Login;

/**
 * Update the staging site
 * Requirements:
 * - Staging site named 'staging'
 */
class updateCest
{

    /**
     * @env single
     */
    public function prepareContent(WebdriverTester $I, Login $login)
    {
        // Login to production site
	    $I->loginAsAdmin(10, 3);

        // Reset content!
        // Gutenberg editor used
        try {

            // Reset content of production site:
            // Set production site page title to 'Updated'
            // Set staging site page title to 'Needs update'
            $I->amOnPage('/wp-admin/edit.php');
            $I->click('//*[@id="post-1"]/td[1]/strong/a');

            // Close the modal "welcome to the wonderful world of blocks..."
            $I->click('.components-modal__header button');

            $I->wait(1);
            $I->click('#post-title-0');
            /* Select the entire text with ctrl + a and delete it with BACKSPACE.
             * This is the only way how it works for WordPress Gutenberg editor as it is written in REACT.
             * In REACT the test appends a string to the text field instead replacing it when using fillField. 'clearField' is not working either.
             * E.g. Replace world with hello and you'll get 'hellworld' instead 'hello'
             * */
            $I->pressKey('#post-title-0', array('ctrl', 'a'));
            $I->pressKey('#post-title-0', \Facebook\WebDriver\WebDriverKeys::BACKSPACE);
            $I->fillField('#post-title-0', 'Updated');

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
            $login->loginOnStagingSite('staging');

            // Change content of staging site /staging
            $I->amOnPage('/staging/wp-admin/edit.php');
            $I->click('//*[@id="post-1"]/td[1]/strong/a');

            $I->Click('#post-title-0');
            $I->pressKey('#post-title-0', array('ctrl', 'a'));
            $I->pressKey('#post-title-0', \Facebook\WebDriver\WebDriverKeys::BACKSPACE);
            $I->fillField('#post-title-0', 'Needs update');
            $I->click('.editor-post-publish-button');
	        $I->waitForJqueryAjax(30);
	        $I->wait(1);
            // Legacy WordPress editor used
        } catch (Exception $e) {

	        // Re-throw, since the test environment is using only Gutenberg for now, and any throw in
	        // the code above will be interpreted as being using the "legacy" editor, which might not be true.
	        throw $e;

            // Reset content of production site
            $I->amOnPage('/wp-admin/edit.php');
            $I->click('//*[@id="post-1"]/td[1]/strong/a');
            $I->fillField('#title', 'Updated');
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
            $login->doLogin();
            // Chenge content of staging site
            $I->amOnPage('/staging/wp-admin/edit.php');
            $I->click('//*[@id="post-1"]/td[1]/strong/a');
            $I->fillField('#title', 'Needs update');
            $I->click('#publish');
        }
    }

    /**
     * @env single
     */
    public function updateContent(WebdriverTester $I, Login $login)
    {
        // Click on the button UPDATE
        $I->amOnPage('/wp-admin/admin.php?page=wpstg_clone');
        $I->waitForElement('//*[@id="staging"]/a[3]', 3);
        $I->click('//*[@id="staging"]/a[3]');

        // Confirm and start the update process
        $I->waitForElement('//*[@id="wpstg-start-updating"]', 3);
        $I->click('//*[@id="wpstg-start-updating"]');
        $I->acceptPopup();

        $I->waitForText('The job finished!', 300, '.wpstg-log-details');

        // Verify updated staging site
        $login->loginOnStagingSite('staging');
        $I->amOnPage('/staging');
        $I->see('Updated');
    }

    /**
     * @env single
     */
    public function checkUpdatedContent(WebdriverTester $I)
    {
        // Reset Content On Production Site
        // Gutenberg editor used
        try {

            // Reset content of production site:
            $I->amOnPage('/wp-admin/edit.php');
            $I->click('//*[@id="post-1"]/td[1]/strong/a');
            $I->Click('#post-title-0');
            /* Select the entire text with ctrl + a and delete it with BACKSPACE.
             * This is the only way how it works for WordPress Gutenberg editor as it is written in REACT.
             * In REACT the test appends a string to the text field instead replacing it when using fillField. 'clearField' is not working either.
             * E.g. Replace world with hello and you'll get 'hellworld' instead 'hello'
             * */
            $I->pressKey('#post-title-0', array('ctrl', 'a'));
            $I->pressKey('#post-title-0', \Facebook\WebDriver\WebDriverKeys::BACKSPACE);
            $I->fillField('#post-title-0', 'Reset content');

            $I->click('.editor-post-publish-button');
            $I->waitForJqueryAjax(30);
            $I->wait(1);
        } catch (Exception $e) {
            // Re-throw the exception, as we are using Gutenberg only
            throw new RuntimeException($e);

            // Legacy WordPress editor used
            // Reset content of production site
            $I->amOnPage('/wp-admin/edit.php');
            $I->click('//*[@id="post-1"]/td[1]/strong/a');
            $I->fillField('#title', 'Reset content');
            $I->click('#publish');
        }
    }
}
