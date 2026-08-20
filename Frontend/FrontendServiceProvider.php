<?php

namespace WPStaging\Frontend;

use WPStaging\Framework\DI\ServiceProvider;

class FrontendServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->registerLoginAfterRestore();
    }





    protected function getMessageAction(): string
    {
 
        $action = 'login_header';

 
        if (file_exists(ABSPATH . WPINC . '/version.php')) {
            require ABSPATH . WPINC . '/version.php';

            if (isset($GLOBALS['wp_version']) && version_compare($GLOBALS['wp_version'], '4.6', '<')) {
 
                $action = 'login_footer';
            }
        }

        return $action;
    }




    private function registerLoginAfterRestore()
    {
        add_action($this->getMessageAction(), [$this->container->make(LoginAfterRestore::class), 'showMessage'], 10, 0); // phpcs:ignore WPStaging.Security.FirstArgNotAString, WPStaging.Security.AuthorizationChecked
    }
}
