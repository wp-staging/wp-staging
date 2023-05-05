<?php

namespace WPStaging\Frontend;

use WPStaging\Framework\DI\ServiceProvider;

class FrontendServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->registerLoginAfterRestore();
    }

    private function registerLoginAfterRestore()
    {
        // Available in WordPress 4.6+
        $action = 'login_header';

        /** @see wp_version_check() */
        if (file_exists(ABSPATH . WPINC . '/version.php')) {
            require ABSPATH . WPINC . '/version.php';

            if (isset($wp_version) && version_compare($wp_version, '4.6', '<')) {
                // Available in WordPress >3.1
                $action = 'login_footer';
            }
        }

        add_action($action, [$this->container->make(LoginAfterRestore::class), 'showMessage'], 10, 0);
    }
}
