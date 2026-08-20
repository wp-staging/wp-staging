<?php

namespace WPStaging\Framework\Utils;











class Env
{





    public static function get(string $name)
    {
 
 
        if (function_exists('getenv')) {
            $value = getenv($name);
            if (is_string($value)) {
                return $value;
            }
        }

        foreach ([$_SERVER, $_ENV] as $source) {
            if (isset($source[$name]) && is_scalar($source[$name])) {
                return (string)$source[$name];
            }
        }

        return false;
    }
}
