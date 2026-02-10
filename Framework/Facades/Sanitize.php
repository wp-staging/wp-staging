<?php

namespace WPStaging\Framework\Facades;

use WPStaging\Framework\Utils\Sanitize as UtilsSanitize;

/**
 * @method static string sanitizeString(mixed $value, bool $shouldUrlDecode = true)
 * @method static string[] sanitizeArrayString(array $items)
 * @method static string sanitizePassword(string $value)
 * @method static int sanitizeInt(string $value)
 * @method static bool sanitizeBool(int|bool|string $value)
 * @method static string sanitizeEmail(string $value)
 * @method static string sanitizeUrl(string $value)
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
