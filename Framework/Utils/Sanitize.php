<?php

namespace WPStaging\Framework\Utils;

class Sanitize
{
    public function sanitizeString($value)
    {
        return htmlspecialchars($value);
    }

    public function sanitizeInt($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT);
    }

    public function sanitizeBool($value)
    {
        // FILTER_VALIDATE_BOOL is alias of FILTER_VALIDATE_BOOLEAN and was introduced in PHP 8.0 but php.net say that we use the BOOL variant,
        // See if we should use like this or just use the BOOLEAN variant?
        return filter_var($value, defined('FILTER_VALIDATE_BOOL') ? FILTER_VALIDATE_BOOL : FILTER_VALIDATE_BOOLEAN);
    }

    public function sanitizeEmail($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}
