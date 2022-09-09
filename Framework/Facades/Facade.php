<?php

namespace WPStaging\Framework\Facades;

use Exception;
use ReflectionMethod;
use RuntimeException;
use WPStaging\Core\WPStaging;

/**
 * Class Facade
 *
 * As the name suggest it works and behaves as a Laravel Facade but without the mockery
 * It still has swapInstance static method to make mocking easy
 *
 * @package WPStaging\Framework\Facades
 */
abstract class Facade
{
    protected static $facadeInstances = [];

    /**
     * Caution: Use in testing Only
     * It replace the current instance with the given instance and return old instance
     * @param self $instance
     * @return self
     * @throws RuntimeException
     */
    public static function swapInstance($instance)
    {
        $oldInstance = static::$facadeInstances[static::getFacadeAccessor()];
        static::setInstance($instance);
        return $oldInstance;
    }

    /**
     * Caution: Use in testing Only
     * @param self $instance
     * @throws RuntimeException
     */
    public static function setInstance($instance)
    {
        $class = static::getFacadeAccessor();
        if ($instance instanceof $class) {
            static::$facadeInstances[static::getFacadeAccessor()] = $instance;
            return;
        }

        throw new RuntimeException('Given instance is not an instance of ' . $class);
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::getInstance();

        if ($instance === null) {
            throw new RuntimeException('A facade instance cannot be created!');
        }

        if (!method_exists($instance, $method)) {
            throw new RuntimeException('Method does not exists!');
        }

        $reflection = new ReflectionMethod($instance, $method);
        if (!$reflection->isPublic()) {
            throw new RuntimeException('Can only call a public method!');
        }

        return $instance->$method(...$args);
    }

    protected static function createInstance()
    {
        try {
            static::$facadeInstances[static::getFacadeAccessor()] = WPStaging::make(static::getFacadeAccessor());
        } catch (Exception $ex) {
            static::$facadeInstances[static::getFacadeAccessor()] = null;
        }
    }

    /** @return self */
    protected static function getInstance()
    {
        if (!isset(static::$facadeInstances[static::getFacadeAccessor()]) || static::$facadeInstances[static::getFacadeAccessor()] === null) {
            static::createInstance();
        }

        return static::$facadeInstances[static::getFacadeAccessor()];
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }
}
