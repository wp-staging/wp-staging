<?php

namespace WPStaging\Basic;

use WPStaging\Basic\Language\Language;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Language\Language as FrameworkLanguage;

/**
 * Class BasicServiceProvider
 *
 * A Service Provider to tell which services to register/bootstrap for the Basic feature.
 * Called at the start of bootstrapping process to make some feature available to the plugin.
 *
 * @package WPStaging\Basic
 */
class BasicServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function registerServiceProvider()
    {
        $this->container->register(BootstrapServiceProvider::class);
    }

    /**
     * Enqueue hooks.
     *
     * @return void
     */
    protected function addHooks()
    {
        Hooks::registerInternalHook(WPStaging::HOOK_BOOTSTRAP_SERVICES, [$this, 'registerServiceProvider']);
        Hooks::registerInternalHook(FrameworkLanguage::HOOK_LOAD_MO_FILES, $this->container->callback(Language::class, 'loadLanguage'));
    }

    /**
     * @return void
     */
    protected function registerClasses()
    {
        // This is to tell the container to use the BASIC feature
        $this->container->setVar('WPSTG_BASIC', true);
    }
}
