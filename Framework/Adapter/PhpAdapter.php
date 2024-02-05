<?php

namespace WPStaging\Framework\Adapter;

use Exception;

/**
 * Class PhpAdapter
 * Adapter class to make sure certain php functions have same behaviour across all PHP versions
 *
 * @package WPStaging\Framework\Adapter
 */
class PhpAdapter
{
    /**
     * Verify that the content of the variable is callable
     * is_callable doesn't return true if it is non static methods written statically in PHP 8,
     * So we fixed that behaviour for PHP 8
     *
     * @param string|null $maybeCallable
     * @return bool
     */
    public function isCallable($maybeCallable): bool
    {
        // Early bail if null
        if ($maybeCallable === null) {
            return false;
        }

        // Using is_callable for all callables in PHP < 8
        // And functions and static methods in PHP >= 8
        if (is_callable($maybeCallable)) {
            return true;
        }

        // Early bail if method is not provided as static
        if (strpos($maybeCallable, "::") === false) {
            return false;
        }

        try {
            list($class, $method) = explode('::', $maybeCallable, 2);
            if (empty($class) || empty($method)) {
                return false;
            }

            // check against only the public methods of class
            return class_exists($class) && in_array($method, get_class_methods($class));
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * @param string $maybeJsonString
     * @return bool
     */
    public function jsonValidate(string $maybeJsonString): bool
    {
        // Only exists from PHP 8.3.0
        if (function_exists('json_validate')) {
            return json_validate($maybeJsonString); // phpcs:ignore
        }

        json_decode($maybeJsonString);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
