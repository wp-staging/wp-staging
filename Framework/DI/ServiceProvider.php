<?php

namespace WPStaging\Framework\DI;

abstract class ServiceProvider extends \WPStaging\Vendor\lucatume\DI52\ServiceProvider
{
    public function register()
    {
        $this->registerClasses();
        $this->addHooks();
    }

    /**
     * Register classes in the container.
     *
     * @return void
     */
    protected function registerClasses()
    {
        // No-op by default.
    }

    /**
     * Enqueue hooks.
     *
     * @return void
     */
    protected function addHooks()
    {
        // No-op by default.
    }
}
