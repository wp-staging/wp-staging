<?php

namespace WPStaging\Framework\Traits;

trait ValueGetterTrait
{
    /**
     * Searches if a key exists in an array and returns it value, returns null otherwise.
     * @param  int|string $key
     * @param  array $haystack
     * @return mixed
     */
    public function getValueFromArray($key, array $haystack)
    {
        return array_key_exists($key, $haystack) ? $haystack[$key] : null;
    }
}
