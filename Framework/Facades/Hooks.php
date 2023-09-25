<?php

namespace WPStaging\Framework\Facades;

use WPStaging\Framework\Utils\Hooks as WpstgHooks;

/**
 * @method static void doAction(string $hook, array ...$args)
 * @method static mixed applyFilters(string $hook, mixed $value, array ...$args)
 */
class Hooks extends Facade
{
    protected static function getFacadeAccessor()
    {
        return WpstgHooks::class;
    }
}
