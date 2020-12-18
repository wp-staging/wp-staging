<?php

namespace WPStaging\Framework\DI;

abstract class ServiceProvider extends \WPStaging\Vendor\tad_DI52_ServiceProvider
{
    final public function register()
    {
        $this->registerClasses();
        $this->addHooks();
    }

    /**
     * Register classes in the container.
     *
     * @return void
     */
    abstract public function registerClasses();

    /**
     * Enqueue hooks.
     *
     * @return void
     */
    abstract public function addHooks();
}
