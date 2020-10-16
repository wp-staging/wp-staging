<?php

namespace WPStaging\Framework\Container;

use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Container implements ContainerInterface
{
    /** @var array */
    private $container = [];

    /** @var array */
    private $parameters = [];

    /** @var array */
    private $mapping = [];

    public function __construct(array $config = [])
    {
        $this->setContainerByConfig($config);
        $this->setParametersByConfig($config);
        $this->setMappingByConfig($config);
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @param string $id
     *
     * @return $this|mixed|object|null
     */
    public function get($id)
    {
        if (self::class === $id) {
            return $this;
        }

        if (!$this->has($id)) {
            /** @noinspection PhpUnhandledExceptionInspection */
            return $this->loadClass($id);
        }

        if (is_object($this->container[$id])) {
            return $this->container[$id];
        }

        if (is_callable($this->container[$id])) {
            $this->container[$id] = $this->container[$id]();
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->loadClass($id);

        return $this->container[$id];
    }

    /**
     * @param string $id
     * @param object|callable|null $value
     */
    public function set($id, $value = null)
    {
        $this->container[$id] = $value;
    }

    /**
     * @param string $id
     */
    public function remove($id)
    {
        if (!$this->has($id)) {
            return;
        }

        unset($this->container[$id]);
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has($id)
    {
        return isset($this->container[$id]);
    }

    /**
     * @noinspection PhpUnused
     * @param string $id
     * @param array $options
     */
    public function setInitialized($id, array $options = [])
    {
        $this->container[$id] = $options;
        $this->get($id);
    }

    /**
     * @noinspection PhpUnused
     * @param string $key
     * @param mixed $value
     */
    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function getParameter($key = null, $default = null)
    {
        if (null === $key) {
            return $this->parameters;
        }

        if ($this->hasParameter($key)) {
            return $this->parameters[$key];
        }

        if (false === strpos($key, '.')) {
            return $default;
        }

        $params = $this->parameters;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($params) || !$this->hasParameter($segment)) {
                return $default;
            }

            $items = &$items[$segment];
        }

        return $params;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasParameter($key)
    {
        return isset($this->parameters[$key]);
    }

    private function setContainerByConfig(array $config = [])
    {
        if (!isset($config['services'])) {
            return;
        }

        foreach ($config['services'] as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $key = $value;
            }
            $this->container[$key] = $value;
        }
    }

    private function setParametersByConfig(array $config = [])
    {
        if (!isset($config['params'])) {
            return;
        }

        foreach ($config['params'] as $key => $value) {
            $this->parameters[$key] = $value;
        }
    }

    private function setMappingByConfig(array $config = [])
    {
        if (!isset($config['mapping'])) {
            return;
        }

        foreach ($config['mapping'] as $key => $value) {
            if (is_scalar($value)) {
                $this->mapping[$key] = $value;
            }
        }
    }

    /**
     * @param string $id
     *
     * @return object|string
     * @throws InvalidConstructorParamException
     * @throws ReflectionException
     */
    private function resolve($id)
    {
        if (!class_exists($id)) {
            return $id;
        }

        $reflection = $this->getReflection($id);

        if (!$reflection) {
            return $id;
        }

        return $this->newInstance($reflection);
    }

    private function getReflection($class)
    {
        try {
            return new ReflectionClass($class);
        }
        catch (ReflectionException $e) {
            return null;
        }
    }

    /**
     * @param ReflectionClass|null $reflection
     *
     * @return null|object
     * @throws InvalidConstructorParamException
     * @throws ReflectionException
     */
    private function newInstance($reflection = null)
    {
        if (!$reflection) {
            return null;
        }

        if (!$reflection->getConstructor()) {
            return $reflection->newInstance();
        }

        $params = $reflection->getConstructor()->getParameters();
        // TODO PHP7.0; $this->container[$reflection->getName()] ?? [];
        if (isset($this->container[$reflection->getName()]) && is_array($this->container[$reflection->getName()])) {
            $options = $this->container[$reflection->getName()];
        }
        else {
            $options = [];
        }

        $args = [];
        foreach ($params as $param) {
            $args[] = $this->getConstructorParam($param, $options);
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * @param ReflectionParameter $param
     * @param array $options
     *
     * @return mixed|object|null
     * @throws InvalidConstructorParamException
     * @throws ReflectionException
     */
    private function getConstructorParam(ReflectionParameter $param, array $options = [])
    {
        if ($param->getClass()) {
            return $this->getConstructorParamClass($param->getClass());
        }

        // Check param values
        if (isset($options[$param->getName()])) {
            return $this->getConstructorParamValue($options[$param->getName()]);
        }

        if (isset($this->parameters[$param->getName()])) {
            return $this->getConstructorParamValue($this->parameters[$param->getName()]);
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($param->allowsNull()) {
            return null;
        }

        throw new InvalidConstructorParamException($param->getName());
    }

    private function getConstructorParamClass(ReflectionClass $class)
    {
        if (isset($this->mapping[$class->getName()])) {
            return $this->get($this->mapping[$class->getName()]);
        }

        return $this->get($class->getName());
    }

    /**
     * @param null|array|string $value
     *
     * @return null|object|array|string
     */
    private function getConstructorParamValue($value = null)
    {
        if (!is_string($value)) {
            return $value;
        }

        if (class_exists($value)) {
            return $this->get($value);
        }

        if (!preg_match_all('#{{(.*?)}}#', $value, $matches)) {
            return $value;
        }

        $replace = [];

        foreach ($matches[1] as $key) {
            $replace[] = $this->getParameter($key);
        }

        return str_replace($matches[0], $replace, $value);
    }

    /**
     * @param string $id
     *
     * @return object|null
     * @throws InvalidConstructorParamException
     * @throws ReflectionException
     */
    private function loadClass($id)
    {
        if (isset($this->mapping[$id])) {
            $id = $this->mapping[$id];
        }

        if (!class_exists($id)) {
            return null;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->container[$id] = $this->resolve($id);

        return $this->container[$id];
    }
}
