<?php

namespace WPStaging\Framework\Facades;

use WPStaging\Framework\Utils\Sanitize as UtilsSanitize;

/**
 * @method static array|string sanitizeString(array|string $value)
 * @method static array|string sanitizePassword(string $value)
 * @method static int sanitizeInt(string $value)
 * @method static bool sanitizeBool(int|bool|string $value)
 * @method static string sanitizeEmail(string $value)
 * @method static string sanitizePath(string $value)
 * @method static string htmlDecodeAndSanitize(string $value)
 * @method static array sanitizeFileUpload(array $value)
 * @method static array sanitizeExcludeRules(string $value)
 * @method static array sanitizeArrayInt(array $value)
 * @method static array sanitizeArray(array $value, array $config)
 * @method static string decodeBase64AndSanitize(string $value)
 */
class Sanitize extends Facade
{
    protected static function getFacadeAccessor()
    {
        return UtilsSanitize::class;
    }
}
