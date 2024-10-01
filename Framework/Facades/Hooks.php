<?php

namespace WPStaging\Framework\Facades;

use WPStaging\Framework\Utils\Hooks as WpstgHooks;

/**
 * @method static void doAction(string $hookName, mixed ...$args)
 * @method static mixed applyFilters(string $hookName, mixed $value, mixed ...$args)
 * @method static void registerInternalHook(string $hookName, callable $callback)
 * @method static void unregisterInternalHook(string $hookName)
 * @method static mixed callInternalHook(string $hookName, array $args = [], mixed $defaultValue = null)
 */
class Hooks extends Facade
{
    protected static function getFacadeAccessor()
    {
        return WpstgHooks::class;
    }
}
