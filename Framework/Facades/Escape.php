<?php

namespace WPStaging\Framework\Facades;

use WPStaging\Framework\Utils\Escape as UtilsEscape;

/**
 * @method static string escapeHtml(string $content)
 */
class Escape extends Facade
{
    protected static function getFacadeAccessor()
    {
        return UtilsEscape::class;
    }
}
