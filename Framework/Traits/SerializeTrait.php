<?php

namespace WPStaging\Framework\Traits;

trait SerializeTrait
{
    /**
     * @see https://developer.wordpress.org/reference/functions/is_serialized/
     * @return bool
     */
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
                // Or else fall through.
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
