<?php

// TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Framework\Traits;

use DateTime;
use ReflectionClass;
use ReflectionProperty;

trait ArrayableTrait
{
    /**
     * @return array
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function toArray()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $reflection = new ReflectionClass($this);
        $props = $reflection->getProperties(
            ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE
        );

        $data = [];
        /** @var ReflectionProperty $prop */
        foreach ($props as $prop) {
            $prop->setAccessible(true);
            $value = $prop->getValue($this);

            if ($value instanceof DateTime) {
                $value = $value->format('U');
            }

            if (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            }

            $data[$prop->getName()] = $value;
        }

        return $data;
    }
}
