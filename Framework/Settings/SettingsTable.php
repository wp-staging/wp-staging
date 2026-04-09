<?php

namespace WPStaging\Framework\Settings;

use WPStaging\Framework\Database\CustomTable;

/**
 * Manages the wpstg_settings custom table for storing plugin settings.
 *
 * Uses WordPress object cache (wp_cache_*) for per-request caching.
 */
class SettingsTable extends CustomTable
{
    /** @var string */
    const CACHE_GROUP = 'wpstg_settings';

    /** @var string */
    const CACHE_EXISTS_KEY_SUFFIX = '__exists';

    /**
     * Sentinel value stored in cache to indicate that the key does not exist in the table.
     *
     * @var string
     */
    const CACHE_NOT_FOUND = '__wpstg_not_found__';

    /**
     * @return string
     */
    protected function getTableName()
    {
        return 'wpstg_settings';
    }

    /**
     * @return string
     */
    protected function getTableVersionKey()
    {
        return 'wpstg_settings_table_version';
    }

    /**
     * @return string
     */
    protected function getTableVersion()
    {
        return '1.0.0';
    }

    /**
     * @return string
     */
    protected function getCreateTableSql()
    {
        global $wpdb;
        $collate   = $wpdb->collate;
        $tableName = $this->getFullTableName();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(191) NOT NULL,
            setting_value LONGTEXT DEFAULT NULL,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY setting_key (setting_key)
            )";

        if (!empty($collate)) {
            $sql .= " COLLATE {$collate}";
        }

        return $sql;
    }

    /**
     * Get a setting value from the settings table.
     *
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        $found  = false;
        $cached = wp_cache_get($name, self::CACHE_GROUP, false, $found);

        if ($found) {
            if ($cached !== self::CACHE_NOT_FOUND) {
                return $cached;
            }

            $existsFound  = false;
            $existsCached = wp_cache_get($this->getExistsCacheKey($name), self::CACHE_GROUP, false, $existsFound);
            if ($existsFound && $existsCached) {
                return $cached;
            }

            return $default;
        }

        $this->ensureTable();

        $tableName   = $this->getFullTableName();
        $escapedName = $this->database->escape($name);
        $result      = $this->database->query("SELECT setting_value FROM `{$tableName}` WHERE setting_key = '{$escapedName}'");

        if ($result === false) {
            return $default;
        }

        $row = $this->database->fetchAssoc($result);

        if (!$row) {
            wp_cache_set($name, self::CACHE_NOT_FOUND, self::CACHE_GROUP);
            wp_cache_set($this->getExistsCacheKey($name), false, self::CACHE_GROUP);
            return $default;
        }

        $value = maybe_unserialize($row['setting_value']);
        wp_cache_set($name, $value, self::CACHE_GROUP);
        wp_cache_set($this->getExistsCacheKey($name), true, self::CACHE_GROUP);

        return $value;
    }

    /**
     * Set a setting value in the settings table.
     *
     * @param string $name
     * @param mixed  $value
     * @return bool
     */
    public function set($name, $value)
    {
        $this->ensureTable();

        $tableName    = $this->getFullTableName();
        $escapedName  = $this->database->escape($name);
        $escapedValue = $this->database->escape(maybe_serialize($value));
        $now          = current_time('mysql');
        $result       = $this->database->query(
            "INSERT INTO `{$tableName}` (setting_key, setting_value, created_at, updated_at) VALUES ('{$escapedName}', '{$escapedValue}', '{$now}', '{$now}') ON DUPLICATE KEY UPDATE setting_value = '{$escapedValue}', updated_at = '{$now}'"
        );

        if ($result !== false) {
            wp_cache_set($name, $value, self::CACHE_GROUP);
            wp_cache_set($this->getExistsCacheKey($name), true, self::CACHE_GROUP);
            return true;
        }

        return false;
    }

    /**
     * Delete a setting from the settings table.
     *
     * @param string $name
     * @return bool
     */
    public function delete($name)
    {
        $this->ensureTable();

        $tableName   = $this->getFullTableName();
        $escapedName = $this->database->escape($name);
        $result      = $this->database->query("DELETE FROM `{$tableName}` WHERE setting_key = '{$escapedName}'");

        wp_cache_delete($name, self::CACHE_GROUP);
        wp_cache_set($this->getExistsCacheKey($name), false, self::CACHE_GROUP);

        return $result !== false;
    }

    /**
     * Invalidates all cached data for this table.
     */
    public function invalidateCache()
    {
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group(self::CACHE_GROUP);
        } elseif (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }

    /**
     * Check if a setting exists in the settings table.
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        $existsCacheKey = $this->getExistsCacheKey($name);
        $found          = false;
        $cachedExists   = wp_cache_get($existsCacheKey, self::CACHE_GROUP, false, $found);

        if ($found) {
            return (bool)$cachedExists;
        }

        $this->ensureTable();

        $tableName   = $this->getFullTableName();
        $escapedName = $this->database->escape($name);
        $result      = $this->database->query("SELECT 1 FROM `{$tableName}` WHERE setting_key = '{$escapedName}' LIMIT 1");

        if ($result === false) {
            return false;
        }

        $exists = $this->database->numRows($result) > 0;
        wp_cache_set($existsCacheKey, $exists, self::CACHE_GROUP);

        return $exists;
    }

    /**
     * @param string $name
     * @return string
     */
    private function getExistsCacheKey($name)
    {
        return $name . self::CACHE_EXISTS_KEY_SUFFIX;
    }
}
