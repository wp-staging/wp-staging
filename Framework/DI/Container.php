<?php

namespace WPStaging\Framework\DI;

use WPStaging\Framework\Interfaces\ShutdownableInterface;
use WPStaging\Framework\DI\Resolver;
use WPStaging\Vendor\lucatume\DI52\Builders\Factory;
use WPStaging\Vendor\Psr\Container\ContainerInterface;
use WPStaging\Vendor\lucatume\DI52\Container as BaseContainer;

class Container extends BaseContainer
{
    /**
     * @var string The PSR-4 namespace prefix we use to isolate third-party dependencies.
     */
    protected $prefix = 'WPStaging\\Vendor\\';

    /**
     * Somehow the singleton version of this child container is not working on unit tests with 3.3.5 version of DI52
     * so we have to use the parent container to make it work for unit tests.
     * @param bool $resolveUnboundAsSingletons
     * @param bool $useBaseContainer
     */
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

    /**
     * @deprecated Currently, all usages of _get in the codebase
     *              are Service Locators, not Dependency Injection.
     *              They need to be refactored in the future.
     *
     * @param $offset
     *
     * @return mixed|null
     */
    public function _get($offset)
    {
        try {
            return $this->offsetGet($offset);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log($e->getMessage());

            return null;
        }
    }

    /**
     * Allows to enqueue the ShutdownableInterface hook
     * on classes requested directly by the application
     * to the container.
     */
    public function get($classOrInterface)
    {
        $instance = parent::get($classOrInterface);
        if (is_object($instance) && $instance instanceof ShutdownableInterface) {
            if (!has_action('shutdown', [$instance, 'onWpShutdown'])) {
                add_action('shutdown', [$instance, 'onWpShutdown']);
            }
        }

        return $instance;
    }

    /**
     * @deprecated 4.1.15
     * Use get instead
     */
    public function make($classOrInterface)
    {
        return $this->get($classOrInterface);
    }

    /**
     * You can use this to store an array of data in the container, without having to worry
     * if the array was already initialized or not.
     *
     * @param $arrayName string The name of the array. If it doesn't exist yet, it will be created.
     * @param $value mixed The value to add to the array.
     *
     * @return bool True if the value was added to the array. False if value already existed in the array.
     */
    public function pushToArray($arrayName, $value)
    {
        try {
            $arrayValues = (array)$this->offsetGet($arrayName);

            if (in_array($value, $arrayValues)) {
                // Do nothing, as the item already exists in this array.
                return false;
            }
        } catch (\Exception $e) {
            // If nothing is set in the container yet, create an empty one.
            $this->setVar($arrayName, []);
            $arrayValues = [];
        }

        // Add this value to the array.
        $arrayValues[] = $value;

        $this->setVar($arrayName, $arrayValues);

        return true;
    }

    /**
     * You can use this to get an array of data in the container, without having to worry
     * if the array was already initialized or not.
     *
     * @param $arrayName string The name of the array. If it doesn't exist yet, an empty array will be returned.
     *
     * @return array The array of data requested, or an empty array if it's not set.
     */
    public function getFromArray($arrayName)
    {
        try {
            return (array)$this->offsetGet($arrayName);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Overloads bind definition binding the prefix as well so that the DI container works locally.
     *
     * @param string     $classOrInterface
     * @param null       $implementation
     * @param array|null $afterBuildMethods
     */
    public function bind($classOrInterface, $implementation = null, array $afterBuildMethods = null)
    {
        if ($this->isDevAutoloader()) {
            parent::bind(str_replace($this->prefix, '', $classOrInterface), $implementation, $afterBuildMethods);
        }

        parent::bind($classOrInterface, $implementation, $afterBuildMethods);
    }

    /**
     * Overloads singleton definition binding the prefix as well so that the DI container works locally.
     *
     * @param string     $classOrInterface
     * @param null       $implementation
     * @param array|null $afterBuildMethods
     */
    public function singleton($classOrInterface, $implementation = null, array $afterBuildMethods = null)
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

    /**
     * Binds the container to the base class name, the current class name and the container interface.
     * Re-adding again from the Parent Container class with some adjustments so it can be called in constructor to keep the container instance singleton
     * @return void
     */
    private function bindThis()
    {
        $this->singleton(ContainerInterface::class, $this);
        $this->singleton(BaseContainer::class, $this);
        $this->singleton(Container::class, $this);
    }
}
