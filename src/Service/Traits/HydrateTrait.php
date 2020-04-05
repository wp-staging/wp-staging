<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x type-hints & return types

namespace WPStaging\Service\Traits;

use DateTime;
use ReflectionException;
use ReflectionMethod;
use WPStaging\Service\Adapter\DateTimeAdapter;
use WPStaging\Service\Entity\EntityException;

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

        if ($value && !$value instanceof DateTime && $param->getClass() && 'DateTime' === $param->getClass()->getName()) {
            $value = (new DateTimeAdapter)->getDateTime($value);
        }

        $method->invoke($this, $value);
    }
}
