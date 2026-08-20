<?php

namespace WPStaging\Framework\Adapter;

use Exception;







class PhpAdapter
{








    public function isCallable($maybeCallable): bool
    {
 
        if ($maybeCallable === null) {
            return false;
        }

 
 
        if (is_callable($maybeCallable)) {
            return true;
        }

 
        if (strpos($maybeCallable, "::") === false) {
            return false;
        }

        try {
            list($class, $method) = explode('::', $maybeCallable, 2);
            if (empty($class) || empty($method)) {
                return false;
            }

 
            return class_exists($class) && in_array($method, get_class_methods($class));
        } catch (Exception $ex) {
            return false;
        }
    }





    public function jsonValidate(string $maybeJsonString): bool
    {
 
        if (function_exists('json_validate')) {
            return json_validate($maybeJsonString); // phpcs:ignore
        }

        json_decode($maybeJsonString);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
