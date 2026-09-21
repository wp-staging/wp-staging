<?php

namespace WPStaging\Staging\Traits;





trait WithStagingOptionsTable
{
    protected function getPrefixedStagingTableName(string $tableName): string
    {
        return $this->requireValidTablePrefix($this->jobDataDto->getDatabasePrefix()) . $tableName;
    }









    protected function insertOption(string $optionName, $optionValue, bool $autoload = false): bool
    {
        $this->deleteOption($optionName);

        $optionTable = $this->getOptionsTableName();
        return $this->executeQuery(
            "INSERT INTO `{$optionTable}` (option_name, option_value, autoload) VALUES (%s, %s, %s)",
            $optionName,
            $optionValue,
            $autoload ? 'on' : 'off'
        );
    }






    protected function updateOption(string $optionName, string $optionValue): bool
    {
        $optionTable = $this->getOptionsTableName();
        return $this->executeQuery(
            "UPDATE `{$optionTable}` SET `option_value` = %s WHERE `option_name` = %s;",
            $optionValue,
            $optionName
        );
    }





    protected function deleteOption(string $optionName): bool
    {
        $optionTable = $this->getOptionsTableName();
        return $this->executeQuery(
            "DELETE FROM `{$optionTable}` WHERE `option_name` = %s;",
            $optionName
        );
    }

    protected function getOptionsTableName(): string
    {
        return $this->getPrefixedStagingTableName('options');
    }

    protected function lastError(): string
    {
        return $this->wpdb->last_error;
    }
}
