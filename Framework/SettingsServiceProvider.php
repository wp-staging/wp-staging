<?php

namespace WPStaging\Framework;

use WPStaging\Framework\Settings\Settings;
use WPStaging\Framework\DI\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    protected function registerClasses()
    {
        $this->container->singleton(Settings::class);
    }

    protected function addHooks()
    {
        add_action('admin_init', $this->container->callback(Settings::class, 'registerSettings'), 100, 1);
    }
}
