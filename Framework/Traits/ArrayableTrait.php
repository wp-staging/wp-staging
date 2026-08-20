<?php

 

namespace WPStaging\Framework\Traits;

trait ArrayableTrait
{




    public function toArray()
    {
 
        $reflection = new \ReflectionClass($this);
        $props = $reflection->getProperties(
            \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE
        );

        $data = [];
 
        foreach ($props as $prop) {
            $propName = $prop->getName();
            if ($propName === 'excludeHydrate') {
                continue;
            }

 
            if (PHP_VERSION_ID < 80100) {
                $prop->setAccessible(true);
            }

            $value = $prop->getValue($this);

            if ($value instanceof \DateTime) {
                $value = $value->format('U');
            }

            if (is_object($value) && method_exists($value, 'toArray')) {
                $value = $value->toArray();
            }

            $data[$propName] = $value;
        }

        return $data;
    }
}
