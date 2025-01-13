<?php

namespace WPStaging\Framework\Facades;

use WPStaging\Core\Utils\Info as CoreInfo;

/**
 * @method static string getOS()
 * @method static bool canUse(string $functionName)
 */
class Info extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CoreInfo::class;
    }
}
