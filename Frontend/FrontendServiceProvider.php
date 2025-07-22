<?php

namespace WPStaging\Frontend;

use WPStaging\Framework\DI\ServiceProvider;

class FrontendServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->registerLoginAfterRestore();
    }

    /**
     * Return either login_header or login_footer depending on whats available
     * @return string
     */
    protected function getMessageAction(): string
    {
        // Available in WordPress 4.6+
        $action = 'login_header';

        /** @see wp_version_check() */
        if (file_exists(ABSPATH . WPINC . '/version.php')) {
            require ABSPATH . WPINC . '/version.php';

            if (isset($GLOBALS['wp_version']) && version_compare($GLOBALS['wp_version'], '4.6', '<')) {
                // Available in WordPress >3.1
                $action = 'login_footer';
            }
        }

        return $action;
    }

    /**
     * @return void
     */
    private function registerLoginAfterRestore()
    {
        add_action($this->getMessageAction(), [$this->container->make(LoginAfterRestore::class), 'showMessage'], 10, 0); // phpcs:ignore WPStaging.Security.FirstArgNotAString, WPStaging.Security.AuthorizationChecked
    }
}
