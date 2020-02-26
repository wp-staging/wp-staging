<?php

// TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Service\Traits;

use DateTime;
use ReflectionClass;
use ReflectionProperty;
use WPStaging\Service\Adapter\DateTimeAdapter;

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
        foreach($props as $prop) {
            $prop->setAccessible(true);
            $value = $prop->getValue($this);

            if ($value instanceof DateTime) {
                $value = (new DateTimeAdapter)->transformToWpFormat($value);
            }

            $data[$prop->getName()] = $value;
        }

        return $data;
    }
}
