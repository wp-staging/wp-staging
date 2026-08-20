<?php

namespace WPStaging\Framework\Traits;

use __PHP_Incomplete_Class;

trait SerializeTrait
{






    protected function safeMaybeUnserialize($data, array $allowedClasses = [], &$rejected = false)
    {
        $rejected = false;

        if (!is_string($data) || !$this->isSerialized($data)) {
            return $data;
        }

        $data          = trim($data);
        $failedToParse = false;
        $value         = $this->unserializeQuietly($data, $allowedClasses, $failedToParse);

        if ($value === false && $failedToParse) {
            $rejected = true;
            return false;
        }

        if ($this->containsForbiddenClass($value)) {
            $rejected = true;
            return false;
        }

        return $value;
    }







    private function unserializeQuietly(string $data, array $allowedClasses, &$failed = false)
    {
        $failed = false;

        set_error_handler(function () use (&$failed) {
            $failed = true;
            return true;
        });

        try {
            return unserialize($data, ['allowed_classes' => $allowedClasses]);
        } finally {
            restore_error_handler();
        }
    }






    protected function containsForbiddenClass($value, int $remainingDepth = 20): bool
    {
        if ($value instanceof __PHP_Incomplete_Class) {
            return true;
        }

        if (is_object($value)) {
            $value = (array)$value;
        }

        if (!is_array($value)) {
            return false;
        }

        if ($remainingDepth < 1) {
            return true;
        }

        foreach ($value as $item) {
            if ($this->containsForbiddenClass($item, $remainingDepth - 1)) {
                return true;
            }
        }

        return false;
    }





    protected function isSerialized(string $data, bool $strict = true): bool
    {
        if (!is_string($data)) {
            return false;
        }

        $data = trim($data);
        if ($data === 'N;') {
            return true;
        }

        if (strlen($data) < 4) {
            return false;
        }

        if ($data[1] !== ':') {
            return false;
        }

        if ($strict) {
            $lastc = substr($data, -1);
            if ($lastc !== ';' && $lastc !== '}') {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace     = strpos($data, '}');
            if ($semicolon === false && $brace === false) {
                return false;
            }

            if ($semicolon !== false && $semicolon < 3) {
                return false;
            }

            if ($brace !== false && $brace < 4) {
                return false;
            }
        }

        $token = $data[0];
        switch ($token) {
            case 's':
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) {
                        return false;
                    }
                } elseif (function_exists('str_contains') && !str_contains($data, '"') || strpos($data, '"') === false) {
                    return false;
                }
 
            case 'a':
            case 'O':
            case 'E':
                return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';
                return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
        }

        return false;
    }
}
