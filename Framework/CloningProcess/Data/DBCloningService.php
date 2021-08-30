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
        // during cloning process we are appending underscore to the staging prefix if it is not there
        // @see WPStaging/Backend/Modules/Jobs/Cloning.php#L293
        // code below solves this issue https://github.com/WP-Staging/wp-staging-pro/issues/385
        $prefix = rtrim($this->dto->getPrefix(), '_') . '_';

        // But if the the clone was created with old WP STAGING version where we were not forcing underscore in prefix,
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

    /**
     * @param $name
     * @return bool|int
     */
    protected function deleteDbOption($name)
    {
        $db = $this->dto->getStagingDb();

        return $db->query(
            $db->prepare(
                "DELETE FROM `{$this->dto->getPrefix()}options` WHERE `option_name` = %s;",
                $name
            )
        );
    }
}
