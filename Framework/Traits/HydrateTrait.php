<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Adapter\DateTimeAdapter;

trait HydrateTrait
{
    /** @var string[] */
    protected $excludeHydrate = [];

    /**
     * @param array $data
     * @return $this
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function hydrate(array $data = [])
    {
        foreach ($data as $key => $value) {
            // Let the child class decide which properties to exclude including the excludeHydrate property itself.
            $propertiesToExclude = array_merge($this->excludeHydrate, ['excludeHydrate']);
            if (in_array($key, $propertiesToExclude, true)) {
                continue;
            }

            /** @noinspection PhpUnhandledExceptionInspection */
            try {
                $this->hydrateByMethod('set' . ucfirst($key), $value);
            } catch (\TypeError $e) {
                $this->debugLog($e->getMessage());
            } catch (\Exception $e) {
                $this->debugLog($e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Use to hydrate public properties
     * @param array $data
     * @return $this
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function hydrateProperties(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                $this->debugLog("Trying to hydrate DTO with property that does not exist. {$key}");
                continue;
            }

            $this->{$key} = $value;
        }

        return $this;
    }

    /**
     * @param string $message
     * @return void
     */
    protected function debugLog(string $message)
    {
        if (!function_exists('\WPStaging\functions\debug_log')) {
            return;
        }

        if (class_exists('\WPStaging\Core\WPStaging') && \WPStaging\Core\WPStaging::areLogsSilenced()) {
            return;
        }

        \WPStaging\functions\debug_log($message);
    }

    /**
     * @param string $method
     * @param mixed $value
     * @return void
     *
     * @throws \ReflectionException
     */
    private function hydrateByMethod(string $method, $value)
    {
        if (!method_exists($this, $method)) {
            if (!is_string($value)) {
                $value = wp_json_encode($value, JSON_UNESCAPED_SLASHES);
            }

            throw new \Exception(sprintf("Trying to hydrate DTO with value that does not exist. %s::%s(%s)", get_class($this), $method, $value));
        }

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $method = new \ReflectionMethod($this, $method);

        $params = $method->getParameters();

        if (!isset($params[0]) || count($params) > 1) {
            throw new \Exception(sprintf(
                'Class %s setter method %s does not have a first parameter or has more than one parameter',
                static::class,
                $method
            ));
        }

        $param = $params[0];

        if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 80000) {
            $class = $param->getType() && !$param->getType()->isBuiltin() ? new \ReflectionClass($param->getType()->getName()) : null;
        } else {
            $class = $param->getClass();
        }

        if (!$value || !$class) {
            $method->invoke($this, $value);
            return;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $method->invoke($this, $this->getClassAsValue($class, $value));
    }

    /**
     * @param \ReflectionClass $class
     * @param mixed $value
     * @return object
     * @throws \Exception
     */
    private function getClassAsValue(\ReflectionClass $class, $value)
    {
        $className = $class->getName();
        if (!$value instanceof \DateTime && $className === 'DateTime') {
            return (new DateTimeAdapter())->getDateTime($value);
        }

        $obj = new $className();
        if (is_array($value) && method_exists($obj, 'hydrate')) {
            $obj->hydrate($value);
        }

        return $obj;
    }
}
