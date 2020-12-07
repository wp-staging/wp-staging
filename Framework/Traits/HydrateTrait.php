<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x type-hints & return types

namespace WPStaging\Framework\Traits;

use DateTime;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use WPStaging\Framework\Entity\EntityException;
use WPStaging\Framework\Adapter\DateTimeAdapter;

trait HydrateTrait
{

    /**
     * @param array $data
     * @return $this
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function hydrate(array $data = [])
    {
        foreach ($data as $key => $value) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->hydrateByMethod('set' . ucfirst($key), $value);
        }

        return $this;
    }

    /**
     * @param string $method
     * @param mixed $value
     *
     * @throws ReflectionException
     */
    private function hydrateByMethod($method, $value)
    {
        if (!method_exists($this, $method)) {
            return;
        }

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $method = new ReflectionMethod($this, $method);

        $params = $method->getParameters();

        if (!isset($params[0]) || count($params) > 1) {
            throw new EntityException(sprintf(
                'Class %s setter method %s does not have a first parameter or has more than one parameter',
                static::class,
                $method
            ));
        }

        $param = $params[0];

        if (!$value || !$param->getClass()) {
            $method->invoke($this, $value);
            return;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $method->invoke($this, $this->getClassAsValue($param->getClass(), $value));
    }

    /**
     * @param ReflectionClass $class
     * @param mixed $value
     * @return object
     * @throws Exception
     */
    private function getClassAsValue(ReflectionClass $class, $value)
    {
        $className = $class->getName();
        if (!$value instanceof DateTime && $className === 'DateTime') {
            return (new DateTimeAdapter)->getDateTime($value);
        }

        $obj = new $className;
        if (is_array($value) && method_exists($obj, 'hydrate')) {
            $obj->hydrate($value);
        }

        return $obj;
    }
}
