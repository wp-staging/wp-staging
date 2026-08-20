<?php

namespace WPStaging\Framework\DI;

use WPStaging\Framework\Interfaces\ShutdownableInterface;
use WPStaging\Framework\DI\Resolver;
use WPStaging\Vendor\lucatume\DI52\Builders\Factory;
use WPStaging\Vendor\Psr\Container\ContainerInterface;
use WPStaging\Vendor\lucatume\DI52\Container as BaseContainer;

class Container extends BaseContainer
{



    protected $prefix = 'WPStaging\\Vendor\\';







    public function __construct($resolveUnboundAsSingletons = false, $useBaseContainer = false)
    {
        if ($useBaseContainer) {
            parent::__construct($resolveUnboundAsSingletons);
            return;
        }

        $this->resolver = new Resolver($resolveUnboundAsSingletons);
        $this->builders = new Factory($this, $this->resolver);
        $this->bindThis();
    }










    public function _get($offset)
    {
        try {
            return $this->offsetGet($offset);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log($e->getMessage());

            return null;
        }
    }






    public function get($classOrInterface)
    {
        $instance = parent::get($classOrInterface);
        if (is_object($instance) && $instance instanceof ShutdownableInterface) {
            if (!has_action('shutdown', [$instance, 'onWpShutdown'])) {
                add_action('shutdown', [$instance, 'onWpShutdown'], ShutdownableInterface::SHUTDOWN_PRIORITY);
            }
        }

        return $instance;
    }





    public function make($classOrInterface)
    {
        return $this->get($classOrInterface);
    }










    public function pushToArray($arrayName, $value)
    {
        try {
            $arrayValues = (array)$this->offsetGet($arrayName);

            if (in_array($value, $arrayValues)) {
 
                return false;
            }
        } catch (\Exception $e) {
 
            $this->setVar($arrayName, []);
            $arrayValues = [];
        }

 
        $arrayValues[] = $value;

        $this->setVar($arrayName, $arrayValues);

        return true;
    }









    public function getFromArray($arrayName)
    {
        try {
            return (array)$this->offsetGet($arrayName);
        } catch (\Exception $e) {
            return [];
        }
    }








    public function bind($classOrInterface, $implementation = null, $afterBuildMethods = null)
    {
        if ($this->isDevAutoloader()) {
            parent::bind(str_replace($this->prefix, '', $classOrInterface), $implementation, $afterBuildMethods);
        }

        parent::bind($classOrInterface, $implementation, $afterBuildMethods);
    }








    public function singleton($classOrInterface, $implementation = null, $afterBuildMethods = null)
    {
        if ($this->isDevAutoloader()) {
            parent::singleton(str_replace($this->prefix, '', $classOrInterface), $implementation, $afterBuildMethods);
        }

        parent::singleton($classOrInterface, $implementation, $afterBuildMethods);
    }

    private function isDevAutoloader()
    {
        if (defined('WPSTG_IS_DEV') && constant('WPSTG_IS_DEV')) {
            return true;
        }

        return defined('WPSTG_IS_DEV_AUTOLOADER') && constant('WPSTG_IS_DEV_AUTOLOADER');
    }






    private function bindThis()
    {
        $this->singleton(ContainerInterface::class, $this);
        $this->singleton(BaseContainer::class, $this);
        $this->singleton(Container::class, $this);
    }
}
