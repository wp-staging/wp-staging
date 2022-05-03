<?php

namespace WPStaging\Framework\CloningProcess\Data;

abstract class DBCloningService extends CloningService
{
    private $optionTable = 'options';

    protected function setOptionTable($tableName = 'options')
    {
        $this->optionTable = $tableName;
    }

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
        // during cloning process we are appending underscore to the staging prefix if it is not there
        // @see WPStaging/Backend/Modules/Jobs/Cloning.php#L293
        // code below solves this issue https://github.com/WP-Staging/wp-staging-pro/issues/385
        $prefix = rtrim($this->dto->getPrefix(), '_') . '_';

        // But if the clone was created with old WP STAGING version where we were not forcing underscore in prefix,
        // this could to let to unwanted results. As we are force copying users and usermeta table into the staging site,
        // It will be good to check against users that without forced appending underscore
        // Code below solves this issue https://github.com/WP-Staging/wp-staging-pro/issues/925
        // If the main job is UPDATE or RESET and users table without post underscore exists then use prefix without post underscore
        if ($this->dto->getMainJob() !== 'cloning' && $this->tableExists($this->dto->getPrefix() . 'users')) {
            $prefix = $this->dto->getPrefix();
        }

        // Skip - Table does not exist
        if (!$this->tableExists($prefix . $table)) {
            $this->log("Table " . $prefix . $table . ' not found. Skipping');
            return true;
        }
        // during update process option table was not skipped even though it was not selected
        // that was causing problem if staging site prefix is basically something string appended to,
        // production site prefix i.e. staging prefix: wp_stagging_ and production prefix: wp_
        if (!in_array($prefix . $table, $this->dto->getTables())) {
            $this->log("Table " . $prefix . $table . ' not selected/updated. Skipping');
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function skipOptionsTable()
    {
        return $this->skipTable($this->optionTable);
    }

    /**
     * @param $name
     * @param $value
     * @return bool|int
     */
    protected function updateDbOption($name, $value)
    {
        $logMessage = "DBCloningService->updateDbOption() SQL: UPDATE FROM {$this->dto->getPrefix()}{$this->optionTable} SET option_value = $value WHERE option_name = $name";
        $this->debugLog($logMessage);

        $result = $this->dto->getStagingDb()->query(
            $this->dto->getStagingDb()->prepare(
                "UPDATE {$this->dto->getPrefix()}{$this->optionTable} SET option_value = %s WHERE option_name = %s",
                $value,
                $name
            )
        );

        if ($result === false) {
            $this->log("DBCloningService->updateDbOption() Error! SQL: " . $logMessage);
            return false;
        }

        return true;
    }

    /**
     * @param $name
     * @param $value
     * @return bool|int Returns false if there is an error. Otherwise the number of inserted tables. Note: It can be 0
     */
    protected function insertDbOption($name, $value)
    {
        $db = $this->dto->getStagingDb();
        $prefix = $this->dto->getPrefix();

        // Don't use UPDATE ON DUPLICATE KEY because wp_options
        // can be messed up and do not have any indexes. This would led to duplicate entries and further issues
        // Instead we remove first and add the row from scratch

        $this->debugLog("DBCloningService->insertDbOption() SQL: DELETE FROM {$prefix}{$this->optionTable} WHERE option_name = $name");

        $db->query(
            $db->prepare(
                "DELETE FROM `{$prefix}{$this->optionTable}` WHERE `option_name` = %s;",
                $name
            )
        );

        $this->debugLog("DBCloningService->insertDbOption() SQL: INSERT INTO {$prefix}{$this->optionTable} ($name, $value)");

        return $db->query(
            $db->prepare(
                "INSERT INTO {$prefix}{$this->optionTable} (option_name,option_value) VALUES (%s,%s)",
                $name,
                $value
            )
        );
    }

    /**
     * @param $name
     * @return bool|int
     */
    protected function deleteDbOption($name)
    {
        $db = $this->dto->getStagingDb();

        return $db->query(
            $db->prepare(
                "DELETE FROM `{$this->dto->getPrefix()}{$this->optionTable}` WHERE `option_name` = %s;",
                $name
            )
        );
    }
}
