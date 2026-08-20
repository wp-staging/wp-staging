<?php

namespace WPStaging\Framework\DI;

abstract class ServiceProvider extends \WPStaging\Vendor\lucatume\DI52\ServiceProvider
{
    public function register()
    {
        $this->registerClasses();
        $this->addHooks();
    }






    protected function registerClasses()
    {
 
    }






    protected function addHooks()
    {
 
    }
}
