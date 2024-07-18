<?php

namespace WPStaging\Framework\Traits;

trait ValueGetterTrait
{
    /**
     * Searches if a key exists in an array and returns it value, returns null otherwise.
     * @param int|string $key
     * @param array $haystack
     * @param mixed $default
     *
     * @return mixed Returns the value of the key if it exists, default value otherwise.
     */
    public function getValueFromArray($key, array $haystack, $default = null)
    {
        return array_key_exists($key, $haystack) ? $haystack[$key] : $default;
    }
}
