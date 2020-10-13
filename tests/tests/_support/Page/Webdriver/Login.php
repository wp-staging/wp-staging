<?php

namespace WPStaging\Tests\Page\Webdriver;

use Codeception\Exception\ModuleException;
use Codeception\Module\WPBrowserMethods;
use WebdriverTester;

/**
 * Staging site login page
 */
class Login
{
    /**
     * @var WebdriverTester;
     */
    protected $I;

    private $loginAttempts = 0;

    public function __construct(WebdriverTester $I)
    {
        $this->I = $I;
    }

    /**
     * Log-in to a Staging site.
     *
     * This is a copy of WPBrowser's loginAs, but adapted for a staging site.
     *
     * @throws \Exception
     */
    public function loginOnStagingSite(string $stagingSite, $username = 'admin', $password = 'password', int $timeout = 10, int $maxAttempts = 5)
    {
        if ($this->loginAttempts === $maxAttempts) {
            throw new ModuleException(
                __CLASS__,
                "Could not login as [{$username}, {$password}] after {$maxAttempts} attempts."
            );
        }

        codecept_debug("Trying to login, attempt {$this->loginAttempts}/{$maxAttempts}...");

        $this->I->amOnPage($stagingSite);

        $this->I->waitForElement('input[name="wpstg-username"]', $timeout);
        $this->I->waitForElement('input[name="wpstg-pass"]', $timeout);
        $this->I->waitForElement('#wp-submit', $timeout);

        $this->I->fillField(['name' => 'wpstg-username'], $username);
        $this->I->fillField(['name' => 'wpstg-pass'], $password);
        $this->I->click('#wp-submit');

        $authCookie    = $this->grabWordPressAuthCookie();
        $loginCookie   = $this->grabWordPressLoginCookie();
        $empty_cookies = empty($authCookie) && empty($loginCookie);

        if ($empty_cookies) {
            $this->loginAttempts++;
            $this->I->wait(1);
            $this->loginOnStagingSite($stagingSite, $username, $password, $timeout, $maxAttempts);
        }

        $this->loginAttempt = 0;
    }

    /**
     * @see \Codeception\Module\WPBrowserMethods::grabWordPressAuthCookie
     */
    private function grabWordPressAuthCookie($pattern = null)
    {
        if ( ! method_exists($this->I, 'grabCookiesWithPattern')) {
            return null;
        }

        $pattern = $pattern ? $pattern : '/^wordpress_[a-z0-9]{32}$/';
        $cookies = $this->I->grabCookiesWithPattern($pattern);

        return empty($cookies) ? null : array_pop($cookies);
    }

    /**
     * @see \Codeception\Module\WPBrowserMethods::grabWordPressLoginCookie
     */
    private function grabWordPressLoginCookie($pattern = null)
    {
        if ( ! method_exists($this->I, 'grabCookiesWithPattern')) {
            return null;
        }

        $pattern = $pattern ? $pattern : '/^wordpress_logged_in_[a-z0-9]{32}$/';
        $cookies = $this->I->grabCookiesWithPattern($pattern);

        return empty($cookies) ? null : array_pop($cookies);
    }

}
