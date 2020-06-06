<?php

/**
 * Update the staging site
 * Requirements:
 * - Staging site named 'staging'
 */
class updateCest
{

    /**
     * @ env single
     * @ env singlesubdir
     * @ env multisite
     */
    public function _before(AcceptanceTester $I)
    {

	// Login to production site
	$I->amOnPage('/wp-login.php');
	$I->wait(1);
	$I->fillField(['name' => 'log'], 'admin');
	$I->fillField(['name' => 'pwd'], 'password');
	$I->click('#wp-submit');

	// Reset content!
	// Gutenberg editor used
	try {

	    // Reset content of production site:
	    // Set production site page header to 'Updated'
	    // Set live site page header to 'Needs update'
	    $I->amOnPage('/wp-admin/edit.php');
	    $I->click('//*[@id="post-1"]/td[1]/strong/a');

	    // Close the box "welcome to the wonderful world of blocks..."
        $I->click('/html/body/div[5]/div/div/div/div/div/div/div/div[1]/button');
	    $I->wait(1);

	    $I->Click('#post-title-0');
	    /* Select the entire text with ctrl + a and delete it with BACKSPACE.
	     * This is the only way how it works for WordPress Gutenberg editor as it is written in REACT.
	     * In REACT the test appends a string to the text field instead replacing it when using fillField. 'clearField' is not working either.
	     * E.g. Replace world with hello and you'll get 'hellworld' instead 'hello'
	     * */
	    $I->pressKey('#post-title-0', array('ctrl', 'a'));
	    $I->pressKey('#post-title-0', WebDriverKeys::BACKSPACE);
	    $I->fillField('#post-title-0', 'Updated');

	    $I->click('.editor-post-publish-button');
	    $I->wait(3);

	    // Open staging site
	    $I->amOnPage('/wp-admin/admin.php?page=wpstg_clone');
	    $I->waitForElementVisible('#staging', 5);
	    $I->click('//*[@id="staging"]/a[2]');

	    // Get name of new window of staging site and switch to
	    $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
		$handles		 = $webdriver->getWindowHandles();
		$lastWindow		 = end($handles);
		$this->lastWindow	 = $lastWindow;
		$webdriver->switchTo()->window($lastWindow);
	    });
	    $I->fillField(['name' => 'wpstg-username'], 'admin');
	    $I->fillField(['name' => 'wpstg-pass'], 'password');
	    $I->click('#wp-submit');
	    $I->waitForText('Dashboard', 5);

	    // Change content of staging site /staging
	    $I->amOnPage('/staging/wp-admin/edit.php');
	    $I->click('//*[@id="post-1"]/td[1]/strong/a');
	    $I->wait(1);

	    $I->Click('#post-title-0');
	    $I->pressKey('#post-title-0', array('ctrl', 'a'));
	    $I->pressKey('#post-title-0', WebDriverKeys::BACKSPACE);
	    $I->fillField('#post-title-0', 'Needs update');
	    $I->click('.editor-post-publish-button');
	    $I->wait(3);
	    // Legacy WordPress editor used
	} catch (Exception $e) {

	    // Reset content of production site
	    $I->amOnPage('/wp-admin/edit.php');
	    $I->click('//*[@id="post-1"]/td[1]/strong/a');
	    $I->fillField('#title', 'Updated');
	    $I->click('#publish');

	    // Open staging site
	    $I->amOnPage('/wp-admin/admin.php?page=wpstg_clone');
	    $I->waitForElementVisible('#staging', 5);
	    $I->click('//*[@id="staging"]/a[2]');
	    $I->wait(1);

	    // Get name of new window of staging site and switch to
	    $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
		$handles		 = $webdriver->getWindowHandles();
		$lastWindow		 = end($handles);
		$this->lastWindow	 = $lastWindow;
		$webdriver->switchTo()->window($lastWindow);
	    });
	    $I->fillField(['name' => 'wpstg-username'], 'admin');
	    $I->fillField(['name' => 'wpstg-pass'], 'password');
	    $I->click('#wp-submit');
	    $I->waitForText('Dashboard', 2);
	    // Chenge content of staging site
	    $I->amOnPage('/staging/wp-admin/edit.php');
	    $I->click('//*[@id="post-1"]/td[1]/strong/a');
	    $I->fillField('#title', 'Needs update');
	    $I->click('#publish');
	}
    }

    public function _after(AcceptanceTester $I)
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
	    $I->pressKey('#post-title-0', WebDriverKeys::BACKSPACE);
	    $I->fillField('#post-title-0', 'Reset content');

	    $I->click('.editor-post-publish-button');
	    $I->wait(3);
	    // Legacy WordPress editor used
	} catch (Exception $e) {

	    // Reset content of production site
	    $I->amOnPage('/wp-admin/edit.php');
	    $I->click('//*[@id="post-1"]/td[1]/strong/a');
	    $I->fillField('#title', 'Reset content');
	    $I->click('#publish');
	}
    }

    /**
     * Update Staging Site
     * @param AcceptanceTester $I
     * @env single
     * @env multisite
     */
    public function updateContent(AcceptanceTester $I)
    {
	// Start Update
	$I->amOnPage('/wp-admin/admin.php?page=wpstg_clone');
	$I->waitForElement('//*[@id="staging"]/a[3]', 3);
	$I->click('//*[@id="staging"]/a[3]');
	$I->waitForElement('//*[@id="wpstg-start-updating"]', 3);

	$I->click('//*[@id="wpstg-start-updating"]');
	$I->acceptPopup();

	$I->waitForText('The job finished!', 300, '.wpstg-log-details');

	// Verify updated staging site
	$I->amOnPage('/staging');
	$I->wait(1);
	$I->fillField(['name' => 'wpstg-username'], 'admin');
	$I->fillField(['name' => 'wpstg-pass'], 'password');
	$I->click('#wp-submit');
	$I->amOnPage('/staging');
	$I->wait(1);
	$I->see('Updated');
    }
}