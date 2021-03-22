<?php

namespace WPStaging\Framework\Database;

class LegacyDatabaseInfo
{
    const PREFIX_AUTOMATIC = 'wpsa';
    const PREFIX_MANUAL = 'wpsm';
    const PREFIX_TMP = 'wpstgtmp';

    /**
     * Returns whether a prefixed table name matches the name used for backup tables or not.
     *
     * @param string $prefixedTableName The prefixed table name.
     *
     * @return bool Whether a prefixed table name matches the name used for backup tables or not.
     */
    public static function isBackupTable($prefixedTableName)
    {
        $pattern = '#^(' . static::PREFIX_AUTOMATIC . '|' . static::PREFIX_MANUAL . ')\\d+_#';

        return (bool)preg_match($pattern, $prefixedTableName);
    }
}
