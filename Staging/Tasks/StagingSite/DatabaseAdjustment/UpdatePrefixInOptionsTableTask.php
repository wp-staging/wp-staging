<?php

namespace WPStaging\Staging\Tasks\StagingSite\DatabaseAdjustment;

use WPStaging\Framework\Database\OptionNameExclusions;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Traits\WordPressOptionNameTrait;
use WPStaging\Staging\Tasks\DatabaseAdjustmentTask;




class UpdatePrefixInOptionsTableTask extends DatabaseAdjustmentTask
{
    use WordPressOptionNameTrait;




    public static function getTaskName()
    {
        return 'staging_update_prefix_options';
    }




    public static function getTaskTitle()
    {
        return 'Update database prefix in options table';
    }




    public function execute()
    {
        $this->setup();
        $currentPrefix = $this->database->getPrefix();
        $stagingPrefix = $this->jobDataDto->getDatabasePrefix();
        if ($stagingPrefix === $currentPrefix) {
            $this->logger->info("Database prefix {$stagingPrefix} is already the same. Skipping for options table.");
            return $this->generateResponse();
        }

        $this->updatePrefixInOptionsTable($currentPrefix, $stagingPrefix);

        return $this->generateResponse();
    }

    protected function updatePrefixInOptionsTable(string $currentPrefix, string $stagingPrefix): bool
    {
        $optionsTableName = $this->getOptionsTableName();
        $this->logger->info("Updating database prefix in {$optionsTableName}.");
        if ($this->isTableExcluded('options')) {
            $this->logger->warning("Table {$optionsTableName} is excluded. Skipping.");
            return true;
        }

        $optionsToIgnore = $this->getOptionsToIgnore();

        $where = "";
        $parameters = [
            $currentPrefix,
            $stagingPrefix,
            $currentPrefix . "%",
        ];

        foreach ($optionsToIgnore as $optionName) {
            $where .= " AND option_name <> %s";
            $parameters[] = $optionName;
        }

        $this->logger->debug("SQL: UPDATE IGNORE {$optionsTableName} SET option_name = replace(option_name, {$currentPrefix}, {$stagingPrefix}) WHERE option_name LIKE {$currentPrefix}%{$where}");

        $update = $this->executeQuery(
            "UPDATE IGNORE `{$optionsTableName}` SET `option_name` = replace(option_name, %s, %s) WHERE `option_name` LIKE %s{$where};",
            ...$parameters
        );

        if ($update === false) {
            $this->logger->error("Failed to update database prefix in option_name of {$optionsTableName}. Error: {$this->lastError()}");
            return false;
        }

        $this->logger->info("Database prefix successfully updated from `{$currentPrefix}` to `{$stagingPrefix}` in {$optionsTableName}.");
        return true;
    }

    protected function getOptionsToIgnore(): array
    {
        $optionNames = array_merge(
            OptionNameExclusions::getFilteredOptionNames(),
            $this->getPrefixIndependentWordPressOptionNames()
        );

        return array_values(array_unique($optionNames));
    }
}
