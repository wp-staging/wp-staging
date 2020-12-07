<?php


namespace WPStaging\Framework\CloningProcess\Data;

abstract class DBCloningService extends CloningService
{
    /**
     * Check if table exists
     * @param string $table
     * @return boolean
     */
    protected function tableExists($table)
    {
        if ($table != $this->dto->getStagingDb()->get_var("SHOW TABLES LIKE '{$table}'")) {
            $this->log("Table {$table} does not exist.");
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    protected function keepPermalinks()
    {
        // Keep Permalinks
        if (isset($this->dto->getSettings()->keepPermalinks) && $this->dto->getSettings()->keepPermalinks === "1") {
            $this->log("\"Keep permalinks\" enabled in settings - skipping");
            return true;
        }
        return false;
    }

    /**
     * @param string $table
     * @return bool
     */
    protected function skipTable($table)
    {
        // Skip - Table does not exist
        if (!$this->tableExists($this->dto->getPrefix() . $table)) {
            $this->log("Table " . $this->dto->getPrefix() . $table . ' not found. Skipping');
            return true;
        }
        //TODO: This check may not be necessary because a non-selected table isn't copied and thus shouldn't exist
        // Skip - Table is not selected or updated.
        //Removed due to issue https://github.com/WP-Staging/wp-staging-pro/issues/385. @todo Delete this later!
/*        if (!in_array($this->dto->getPrefix() . $table, $this->dto->getTables())) {
            $this->log("Table " . $this->dto->getPrefix() . $table . ' not selected/updated. Skipping');
            return true;
        }*/

        return false;
    }

    /**
     * @return bool
     */
    protected function skipOptionsTable()
    {
        return $this->skipTable('options');
    }

    /**
     * @param $name
     * @param $value
     * @return bool|int
     */
    protected function updateDbOption($name, $value)
    {
        return $this->dto->getStagingDb()->query(
            $this->dto->getStagingDb()->prepare(
                "UPDATE {$this->dto->getPrefix()}options SET option_value = %s WHERE option_name = %s",
                $value,
                $name
            )
        );
    }

    /**
     * @param $name
     * @param $value
     * @return bool|int
     */
    protected function insertDbOption($name, $value)
    {
        $db = $this->dto->getStagingDb();
        $prefix = $this->dto->getPrefix();

        // Don't use UPDATE ON DUPLICATE KEY because wp_options
        // can be messed up and do not have any indexes. This would led to duplicate entries and further issues
        // Instead we remove first and add the row from scratch

        $db->query(
            $db->prepare(
                "DELETE FROM `{$prefix}options` WHERE `option_name` = %s;",
                $name
            )
        );
        return $db->query(
            $db->prepare(
                "INSERT INTO {$prefix}options (option_name,option_value) VALUES (%s,%s)",
                $name,
                $value
            )
        );
    }
}
