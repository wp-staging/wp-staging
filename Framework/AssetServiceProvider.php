<?php

namespace WPStaging\Framework;

use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\DI\ServiceProvider;

class AssetServiceProvider extends ServiceProvider
{
    protected function registerClasses()
    {
        $this->container->singleton(Assets::class);
    }

    protected function addHooks()
    {
        add_action('admin_enqueue_scripts', $this->container->callback(Assets::class, 'enqueueElements'), 100, 1);
        add_action('admin_enqueue_scripts', $this->container->callback(Assets::class, 'removeWPCoreJs'), 5, 1);
        add_action('wp_enqueue_scripts', $this->container->callback(Assets::class, 'enqueueElements'), 100, 1);
    }
}
