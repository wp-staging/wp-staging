<?php

namespace WPStaging\Framework\Utils;

/**
 * Reads environment variables without assuming getenv() is callable.
 *
 * getenv() needs no extension, but a host can still drop it through the
 * disable_functions ini directive. PHP >= 8.0 deletes a disabled function from
 * the function table, so calling it directly raises "Call to undefined function"
 * and takes the whole request down; PHP 7 leaves a stub that warns and returns
 * null. function_exists() reports false in both cases, which is the guard used
 * here, with $_SERVER and $_ENV as the fallback source.
 */
class Env
{
    /**
     * @param string $name
     * @return string|false The value, or false when the variable is not set,
     *                      matching getenv()'s own contract.
     */
    public static function get(string $name)
    {
        // Read the live process environment first: it is the only source that
        // reflects a putenv() call made during the current request.
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
