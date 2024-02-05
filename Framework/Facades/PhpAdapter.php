<?php

namespace WPStaging\Framework\Facades;

use WPStaging\Framework\Adapter\PhpAdapter as WpstgPhpAdapter;

/**
 * @method static bool isCallable(string|nulll $maybeCallable)
 * @method static bool jsonValidate(string $maybeJsonString)
 */
class PhpAdapter extends Facade
{
    protected static function getFacadeAccessor()
    {
        return WpstgPhpAdapter::class;
    }
}
