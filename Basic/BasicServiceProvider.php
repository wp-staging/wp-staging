<?php

namespace WPStaging\Basic;

use WPStaging\Basic\Language\Language;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Ajax\Status;
use WPStaging\Framework\Language\Language as FrameworkLanguage;
use WPStaging\Frontend\FrontendServiceProvider;









class BasicServiceProvider extends ServiceProvider
{



    public function registerServiceProvider()
    {
        $this->container->register(BootstrapServiceProvider::class);
        $this->container->register(FrontendServiceProvider::class);

        add_action('wp_ajax_wpstg--job--status', $this->container->callback(Status::class, 'ajaxProcess')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_nopriv_wpstg--job--status', $this->container->callback(Status::class, 'ajaxProcess')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }






    protected function addHooks()
    {
        Hooks::registerInternalHook(WPStaging::HOOK_BOOTSTRAP_SERVICES, [$this, 'registerServiceProvider']);
        Hooks::registerInternalHook(FrameworkLanguage::HOOK_LOAD_MO_FILES, $this->container->callback(Language::class, 'loadLanguage'));
    }




    protected function registerClasses()
    {
 
        $this->container->setVar('WPSTG_BASIC', true);
    }
}
