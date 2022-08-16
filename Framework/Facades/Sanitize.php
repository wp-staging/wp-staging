<?php

namespace WPStaging\Framework\Facades;

use WPStaging\Framework\Utils\Sanitize as UtilsSanitize;

/**
 * @method static string sanitizeString(mixed $value)
 * @method static int|string sanitizeInt(mixed $value)
 * @method static bool sanitizeBool(mixed $value)
 * @method static string sanitizeEmail(mixed $value)
 */
class Sanitize extends Facade
{
    protected static function getFacadeAccessor()
    {
        return UtilsSanitize::class;
    }
}
