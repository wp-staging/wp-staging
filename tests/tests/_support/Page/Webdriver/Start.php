<?php

namespace WPStaging\Tests\Page\Webdriver;

use WebdriverTester;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;

class Start
{
    /**
     * @var WebdriverTester;
     */
    protected $I;

    public function __construct(WebdriverTester $I)
    {
        $this->I = $I;
    }

    /**
     * Navigate to the starting page of WPSTAGING
     */
    public function goHere()
    {
        $this->I->amOnAdminPage('admin.php?page=wpstg_clone');
    }

    /**
     * Deletes all cloned sites
     *
     * @throws \Exception
     */
    public function deleteAllSites()
    {
        $sites = $this->I->grabOptionFromDatabase('wpstg_existing_clones_beta');

        codecept_debug($sites);

        // Early bail: No sites to delete
        if (empty($sites)) {
            codecept_debug('No sites to delete');

            return;
        }

        if (is_countable($sites)) {
            codecept_debug(sprintf('found %d sites to delete in db', count($sites)));
        }

        $this->goHere();
        $this->I->waitForElement('.wpstg-remove-clone');
        $this->I->executeInSelenium(
            function (RemoteWebDriver $webdriver) {
                $clones = $webdriver->findElements(WebDriverBy::cssSelector('.wpstg-clone'));

                codecept_debug(sprintf('found %d sites to delete in DOM', count($clones)));

                /** @var WebDriverElement $clone */
                foreach ($clones as $clone) {
                    $clone->findElement(WebDriverBy::cssSelector('.wpstg-remove-clone'))->click();
                    $this->I->waitForElementVisible('#wpstg-remove-clone');

                    /*
                     * @todo Fire a JS event when animation ends
                     *       so we can use waitForJS()
                     */
                    $this->I->wait(2); // Animation

                    $this->I->retryClick('#wpstg-remove-clone');
                    $this->I->waitForElementNotVisible('#wpstg-remove-clone');
                }
            }
        );
    }

}
