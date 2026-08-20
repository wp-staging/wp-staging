<?php

namespace WPStaging\Framework\Facades;

use Exception;
use ReflectionMethod;
use RuntimeException;
use WPStaging\Core\WPStaging;









abstract class Facade
{
    protected static $facadeInstances = [];








    public static function swapInstance($instance)
    {
        $oldInstance = static::$facadeInstances[static::getFacadeAccessor()];
        static::setInstance($instance);
        return $oldInstance;
    }






    public static function setInstance($instance)
    {
        $class = static::getFacadeAccessor();
        if ($instance instanceof $class) {
            static::$facadeInstances[static::getFacadeAccessor()] = $instance;
            return;
        }

        throw new RuntimeException('Given instance is not an instance of ' . $class);
    }






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

 
    protected static function getInstance()
    {
        if (!isset(static::$facadeInstances[static::getFacadeAccessor()]) || static::$facadeInstances[static::getFacadeAccessor()] === null) {
            static::createInstance();
        }

        return static::$facadeInstances[static::getFacadeAccessor()];
    }








    protected static function getFacadeAccessor()
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }
}
