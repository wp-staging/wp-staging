<?php

namespace WPStaging\Framework\Traits;

trait ValueGetterTrait
{








    public function getValueFromArray($key, array $haystack, $default = null)
    {
        return array_key_exists($key, $haystack) ? $haystack[$key] : $default;
    }
}
