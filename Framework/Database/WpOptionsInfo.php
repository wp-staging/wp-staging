<?php

namespace WPStaging\Framework\Database;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Database;

class WpOptionsInfo
{
 
    private $wpdb;

    public function __construct()
    {
        $this->wpdb = Wpstaging::make(Database::class)->getWpdb();
    }






    public function isOptionTablePrimaryKeyMissing(string $optionTable): bool
    {
        if ($this->isSqliteTranslatorInstance()) {
            return false;
        }

        $fInfo = $this->getFieldInfo('option_id', $optionTable);

 
        if (isset($fInfo->flags) && ($fInfo->flags & MYSQLI_PRI_KEY_FLAG) && $fInfo->flags & MYSQLI_AUTO_INCREMENT_FLAG) {
            return false;
        }

        if ($this->isPrimaryKeyIsOptionName($optionTable)) {
            return false;
        }

        return true;
    }





    public function isPrimaryKeyIsOptionName(string $optionTable): bool
    {
        if ($this->isSqliteTranslatorInstance()) {
            return false;
        }

        $fInfo = $this->getFieldInfo('option_name', $optionTable);
 
        if (!(isset($fInfo->flags) && $fInfo->flags & MYSQLI_PRI_KEY_FLAG)) {
            return false;
        }

 
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




    private function isSqliteTranslatorInstance(): bool
    {
        return !empty($this->wpdb->dbh) && ($this->wpdb->dbh instanceof \WP_SQLite_Translator); // @phpstan-ignore-line
    }
}
