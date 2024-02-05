<?php

namespace WPStaging\Framework\Facades;

use WPStaging\Framework\Utils\Hooks as WpstgHooks;

/**
 * @method static void doAction(string $hookName, mixed ...$args)
 * @method static mixed applyFilters(string $hookName, mixed $value, mixed ...$args)
 */
class Hooks extends Facade
{
    protected static function getFacadeAccessor()
    {
        return WpstgHooks::class;
    }
}
