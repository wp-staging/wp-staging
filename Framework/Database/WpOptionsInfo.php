<?php

namespace WPStaging\Framework\Database;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Database;

class WpOptionsInfo
{
    /** @var mixed|Database */
    private $wpdb;

    public function __construct()
    {
        $this->wpdb = Wpstaging::make(Database::class)->getWpdb();
    }

    /**
     * Check whether the wp_options table is missing primary key | auto increment
     * @param string $optionTable
     * @return bool
     */
    public function isOptionTablePrimaryKeyMissing(string $optionTable): bool
    {
        if ($this->isSqliteTranslatorInstance()) {
            return false;
        }

        $fInfo = $this->getFieldInfo('option_id', $optionTable);

        // Check whether the flag have primary key and auto increment flag
        if (isset($fInfo->flags) && ($fInfo->flags & MYSQLI_PRI_KEY_FLAG) && $fInfo->flags & MYSQLI_AUTO_INCREMENT_FLAG) {
            return false;
        }

        if ($this->isPrimaryKeyIsOptionName($optionTable)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $optionTable
     * @return bool
     */
    public function isPrimaryKeyIsOptionName(string $optionTable): bool
    {
        if ($this->isSqliteTranslatorInstance()) {
            return false;
        }

        $fInfo = $this->getFieldInfo('option_name', $optionTable);
        // Abort if flag has no primary key
        if (!(isset($fInfo->flags) && $fInfo->flags & MYSQLI_PRI_KEY_FLAG)) {
            return false;
        }

        // Check if the field has a composite key
        $results = $this->wpdb->get_results("SELECT `CONSTRAINT_NAME`,`COLUMN_NAME` FROM `information_schema`.`KEY_COLUMN_USAGE` WHERE `table_name`='{$optionTable}' AND `table_schema`=DATABASE()", ARRAY_A);
        if (empty($results) || !is_array($results)) {
            return true;
        }

        $found = 0;
        while ($row = array_shift($results)) {
            if ($row['CONSTRAINT_NAME'] === 'PRIMARY' && in_array($row['COLUMN_NAME'], ['option_name', 'option_id'])) {
                $found++;
            }

            if ($found > 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $fieldName
     * @param string $tableName
     * @return false|object|null
     */
    protected function getFieldInfo(string $fieldName, string $tableName)
    {
        $result = $this->wpdb->dbh->query("SELECT {$fieldName} FROM {$tableName} LIMIT 1");
        if (!is_object($result)) {
            return false;
        }

        $fieldInfo = $result->fetch_field();
        $result->free_result();
        return $fieldInfo;
    }

    /**
     * @return bool
     */
    private function isSqliteTranslatorInstance(): bool
    {
        return !empty($this->wpdb->dbh) && ($this->wpdb->dbh instanceof \WP_SQLite_Translator); // @phpstan-ignore-line
    }
}
