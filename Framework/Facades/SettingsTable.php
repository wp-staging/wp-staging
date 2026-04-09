<?php

namespace WPStaging\Framework\Facades;

use WPStaging\Framework\Settings\SettingsTable as SettingsTableService;

/**
 * @method static mixed get(string $name, mixed $default = null)
 * @method static bool set(string $name, mixed $value)
 * @method static bool delete(string $name)
 * @method static bool has(string $name)
 * @method static string getFullTableName()
 * @method static void ensureTable()
 * @method static void invalidateCache()
 */
class SettingsTable extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return SettingsTableService::class;
    }
}
