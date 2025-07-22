<?php

namespace WPStaging\Staging\Tasks\StagingSite\DatabaseAdjustment;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Staging\Tasks\DatabaseAdjustmentTask;

/**
 * Replacement for WPStaging\Framework\CloningProcess\Data\UpdateWpOptionsTablePrefix
 */
class UpdatePrefixInOptionsTableTask extends DatabaseAdjustmentTask
{
    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'staging_update_prefix_options';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Update database prefix in options table';
    }

    /**
     * @return TaskResponseDto
     */
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
        if ($this->isTableExcluded('usermeta')) {
            $this->logger->warning("Table {$optionsTableName} is excluded. Skipping.");
            return true;
        }

        // Filter the rows below. Do not update them!
        $optionsToIgnore = [
            'wp_mail_smtp',
            'wp_mail_smtp_version',
            'wp_mail_smtp_debug',
            'db_version',
        ];

        $optionsToIgnore = array_merge($optionsToIgnore, Hooks::applyFilters('wpstg_data_excl_rows', []));

        $where = "";
        foreach ($optionsToIgnore as $optionName) {
            $where .= " AND option_name <> '" . $optionName . "'";
        }

        $this->logger->debug("SQL: UPDATE IGNORE {$optionsTableName} SET option_name = replace(option_name, {$currentPrefix}, {$stagingPrefix}) WHERE option_name LIKE {$currentPrefix}%{$where}");

        $update = $this->executeQuery(
            "UPDATE IGNORE `{$optionsTableName}` SET `option_name` = replace(option_name, %s, %s) WHERE `option_name` LIKE %s{$where};",
            $currentPrefix,
            $stagingPrefix,
            $currentPrefix . "%"
        );

        if ($update === false) {
            $this->logger->error("Failed to update database prefix in option_name of {$optionsTableName}. Error: {$this->lastError()}");
            return false;
        }

        $this->logger->info("Database prefix successfully updated from `{$currentPrefix}` to `{$stagingPrefix}` in {$optionsTableName}.");
        return true;
    }
}
