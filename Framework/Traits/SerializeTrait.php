<?php

namespace WPStaging\Framework\Traits;

trait SerializeTrait
{







    protected function safeMaybeUnserialize($data, array $allowedClasses = [], &$rejected = false, &$failedToParse = false)
    {
        $rejected      = false;
        $failedToParse = false;

        if (!is_string($data) || !$this->isSerialized($data)) {
            return $data;
        }

        $data  = trim($data);
        $value = $this->unserializeQuietly($data, $allowedClasses, $failedToParse);

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






    protected function containsForbiddenClass($value, int $maxDepth = 256): bool
    {
        $pending = [[$value, 0]];

        while ($pending !== []) {
            list($current, $depth) = array_pop($pending);

            if ($current instanceof \__PHP_Incomplete_Class) {
                return true;
            }

            if (is_object($current)) {
                $current = (array)$current;
            }

            if (!is_array($current)) {
                continue;
            }

            if ($depth >= $maxDepth) {
                return true;
            }

            foreach ($current as $item) {
                if (is_array($item) || is_object($item)) {
                    $pending[] = [$item, $depth + 1];
                }
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
