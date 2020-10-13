<?php

use WPStaging\Tests\Page\Webdriver\Login;

class pushingCest
{

	public function _before(WebdriverTester $I)
	{
		$I->loginAsAdmin(10, 3);
	}


    /**
     * Create Content to push
     * Reset the parent website and change the staging sites post title
     * @param WebdriverTester $I
     */
    public function createContent(WebdriverTester $I, Login $login)
    {

        // Gutenberg editor used
        try {
            // Reset content of production site
            $I->amOnPage('/wp-admin/edit.php');
            $I->click('/html/body/div[1]/div[2]/div[2]/div[1]/div[3]/form[1]/table/tbody/tr[1]/td[1]/strong/a');

            // Close the box "welcome to the wonderful world of blocks..."
            $I->click('.components-modal__header button');

	        /* Select the entire text with ctrl + a and delete it with BACKSPACE.
			 * This is the only way how it works for WordPress Gutenberg editor as it is written in REACT.
			 * In REACT the test appends a string to the text field instead replacing it when using fillField. 'clearField' is not working either.
			 * E.g. Replace world with hello and you'll get 'hellworld' instead 'hello'
			 * */
	        $I->click('#post-title-0');
	        $I->pressKey('#post-title-0', array('ctrl', 'a'));
	        $I->pressKey('#post-title-0', \Facebook\WebDriver\WebDriverKeys::BACKSPACE);

	        $I->fillField('#post-title-0', 'Reset Content');

            $I->click('.editor-post-publish-button');
            $I->waitForJqueryAjax(30);
            $I->wait(1);

            // Open staging site
            $I->amOnPage('/wp-admin/admin.php?page=wpstg_clone');
            $I->waitForElementVisible('#staging', 5);
            $I->wait(1);
            $I->click('//*[@id="staging"]/a[2]');

            // Get name of new window of staging site and switch to
            $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
                $handles = $webdriver->getWindowHandles();
                $lastWindow = end($handles);
                $this->lastWindow = $lastWindow;
                $webdriver->switchTo()->window($lastWindow);
            });
            $login->loginOnStagingSite('staging');

            // Chenge content of staging site
            $I->amOnPage('/staging/wp-admin/edit.php');
            $I->click('/html/body/div[1]/div[2]/div[2]/div[1]/div[3]/form[1]/table/tbody/tr[1]/td[1]/strong/a');
            $I->Click('#post-title-0');
            $I->pressKey('#post-title-0', array('ctrl', 'a'));
            $I->pressKey('#post-title-0', \Facebook\WebDriver\WebDriverKeys::BACKSPACE);
            $I->fillField('#post-title-0', 'Pushed Content');
            $I->click('.editor-post-publish-button');
	        $I->waitForJqueryAjax(30);
	        $I->wait(1);
        } catch (Exception $e) {
            // Legacy WordPress editor used

	        // Re-throw, since the test environment is using only Gutenberg for now, and any throw in
	        // the code above will be interpreted as being using the "legacy" editor, which might not be true.
        	throw $e;

            // Reset content of production site
            $I->amOnPage('/wp-admin/edit.php');
            $I->click('/html/body/div[1]/div[2]/div[2]/div[1]/div[3]/form[1]/table/tbody/tr[1]/td[1]/strong/a');
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
            $login->doLogin();
            // Chenge content of staging site
            $I->amOnPage('/staging/wp-admin/edit.php');
            $I->click('/html/body/div[1]/div[2]/div[2]/div[1]/div[3]/form[1]/table/tbody/tr[1]/td[1]/strong/a');
            $I->fillField('#title', 'Pushed Content');
            $I->click('#publish');
        }
    }

    /**
     * @env single
     *
     * @param WebdriverTester $I
     */
    public function addPluginSingle(WebdriverTester $I)
    {

        system('rm -R /var/www/single_tests/staging/wp-content/plugins/foo');

        $I->wait(3);

        if (!mkdir('/var/www/single_tests/staging/wp-content/plugins/foo') && !is_dir('/var/www/single_tests/staging/wp-content/plugins/foo')) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', '/var/www/single_tests/staging/wp-content/plugins/foo'));
        }
        file_put_contents('/var/www/single_tests/staging/wp-content/plugins/foo/foo.php',
            <<<PLUGIN
<?php
/* Plugin name: PluginInsideFolder */
PLUGIN
        );
        system('chown -R www-data:www-data /var/www/single_tests/staging/wp-content/plugins');

        // Open staging site
        $I->amOnPage('/staging/wp-admin/plugins.php');
        $I->fillField('#user_login', 'admin' );
        $I->fillField('#user_pass', 'password' );
        $I->click('#wp-submit');
        $I->waitForText('Dashboard', 2);
        $I->amOnPage('/staging/wp-admin/plugins.php');
        $I->see('PluginInsideFolder');
    }

    /**
     * @env multi
     *
     * @param WebdriverTester $I
     */
    public function addPluginMulti(WebdriverTester $I) {

        system('rm -R /var/www/multi_tests/staging/wp-content/plugins/foo');

        $I->wait(3);

        if (!mkdir('/var/www/multi_tests/staging/wp-content/plugins/foo') && !is_dir('/var/www/multi_tests/staging/wp-content/plugins/foo')) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', '/var/www/multi_tests/staging/wp-content/plugins/foo'));
        }
        file_put_contents('/var/www/multi_tests/staging/wp-content/plugins/foo/foo.php',
            <<<PLUGIN
<?php
/* Plugin name: PluginInsideFolder */
PLUGIN
        );
        system('chown -R www-data:www-data /var/www/multi_tests/staging/wp-content/plugins');

        // Open staging site
        $I->amOnPage('/staging/wp-admin/plugins.php');
        $I->fillField('#user_login', 'admin' );
        $I->fillField('#user_pass', 'password' );
        $I->click('#wp-submit');
        $I->waitForText('Dashboard', 2);
        $I->amOnPage('/staging/wp-admin/plugins.php');
        $I->see('PluginInsideFolder');
    }

    /**
     * Push to same database for single and multisite env
     * All tables
     * All folder
     * Main Database
     * @param WebdriverTester $I
     */
    public function pushing(WebdriverTester $I)
    {
	    $I->amOnPage('/wp-admin/admin.php?page=wpstg_clone');
	    $I->waitForJqueryAjax(10);
	    $I->waitForElementVisible('#staging a.wpstg-push-changes', 2);
        $I->click('#staging a.wpstg-push-changes');
        $I->waitForElementVisible('.wpstg-tabs-wrapper', 2);
        $I->click('#wpstg-push-changes');
        $I->acceptPopup();
        $I->waitForText('Finished', 400, '.wpstg-loader');
        //$I->see('Clone job\'s cache files have been deleted!');
        $I->amOnPage('/');
        $I->see('Pushed Content');
        $I->amOnPluginsPage();
        $I->see('PluginInsideFolder');
    }
}
