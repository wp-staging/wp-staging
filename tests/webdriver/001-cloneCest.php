<?php

class cloningCest
{

    public function _before(WebdriverTester $I)
    {
    	$I->checkEnvFlag();
    	$I->loginAsAdmin(10, 3);
	    $I->amOnPage('/wp-admin/admin.php?page=wpstg_clone');
    }

    public function deleteSite(WebdriverTester $I)
    {
        try {
            $I->see('staging', '#staging');
            $I->click('#staging .wpstg-remove-clone');
            $I->waitForElementVisible("#wpstg-remove-clone", 7);

            // Wait for animations
            $I->wait(1);

            $I->click('#wpstg-remove-clone');
            $I->waitForElementNotVisible('#wpstg-removing-clone', 60);
        } catch (Exception $e) {
            return;
        }
    }

    public function cloneSite(WebdriverTester $I)
    {
        // Create new Staging site
    	// TODO specify a locator on next $I->see, such as .foo, so that smart wait works.
	    $I->wait(1);
        $I->see('Create New Staging Site');
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
        $I->fillField(['name' => 'wpstg-username'], 'admin');
        $I->fillField(['name' => 'wpstg-pass'], 'password');
        $I->click('#wp-submit');
        $I->see('Dashboard');
        $I->amOnPage('/staging/wp-admin/admin.php?page=wpstg_clone');
        $I->see('This staging site can be pushed and modified with WP Staging Pro plugin', '.wpstg-notice-alert');
    }

}
