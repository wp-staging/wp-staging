<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Job as MainJob;

abstract class DBCloningService extends CloningService
{
    private $optionTable = 'options';

    protected function setOptionTable($tableName = 'options')
    {
        $this->optionTable = $tableName;
    }






    protected function tableExists($table)
    {
        if ($table != $this->dto->getStagingDb()->get_var("SHOW TABLES LIKE '{$table}'")) {
            $this->log("Table {$table} does not exist.");
            return false;
        }

        return true;
    }




    protected function keepPermalinks()
    {
 
        if (isset($this->dto->getSettings()->keepPermalinks) && $this->dto->getSettings()->keepPermalinks === "1") {
            $this->log("\"Keep permalinks\" enabled in settings - skipping");
            return true;
        }

        return false;
    }





    public function skipTable($table)
    {
 
 
 
        $prefix = rtrim($this->dto->getPrefix(), '_') . '_';

 
 
 
 
 
        if ($this->dto->getMainJob() !== MainJob::STAGING && $this->tableExists($this->dto->getPrefix() . 'users')) {
            $prefix = $this->dto->getPrefix();
        }

 
        if (!$this->tableExists($prefix . $table)) {
            $this->log("Table " . $prefix . $table . ' not found. Skipping');
            return true;
        }

 
 
 
        if (!in_array($prefix . $table, $this->dto->getTables())) {
            $this->log("Table " . $prefix . $table . ' not selected/updated. Skipping');
            return true;
        }

        return false;
    }




    protected function skipOptionsTable()
    {
        return $this->skipTable($this->optionTable);
    }






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






    protected function insertDbOption($name, $value)
    {
        $db = $this->dto->getStagingDb();
        $prefix = $this->dto->getPrefix();

 
 
 

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
