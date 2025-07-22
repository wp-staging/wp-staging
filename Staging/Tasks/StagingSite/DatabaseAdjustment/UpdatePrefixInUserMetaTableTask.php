<?php

namespace WPStaging\Staging\Tasks\StagingSite\DatabaseAdjustment;

use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Staging\Tasks\DatabaseAdjustmentTask;

/**
 * Replacement for WPStaging\Framework\CloningProcess\Data\UpdateTablePrefix
 */
class UpdatePrefixInUserMetaTableTask extends DatabaseAdjustmentTask
{
    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'staging_update_prefix_usermeta';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Update database prefix in usermeta table';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute()
    {
        $this->setup();

        $usermetaTable = $this->getPrefixedStagingTableName('usermeta');
        $this->logger->info("Updating database prefix in {$usermetaTable}.");
        if ($this->isTableExcluded('usermeta')) {
            $this->logger->warning("Table {$usermetaTable} is excluded. Skipping.");
            return $this->generateResponse();
        }

        $stagingPrefix = $this->jobDataDto->getDatabasePrefix();
        $currentPrefix = $this->database->getPrefix();
        if ($this->jobDataDto->getIsStagingNetwork()) {
            $currentPrefix = $this->database->getBasePrefix();
        }

        if ($stagingPrefix === $currentPrefix) {
            $this->logger->info("Database prefix {$stagingPrefix} is already the same for table {$usermetaTable}.");
            return $this->generateResponse();
        }

        $this->logger->debug("SQL: UPDATE {$usermetaTable} SET meta_key = replace(meta_key, {$currentPrefix}, {$stagingPrefix}) WHERE meta_key LIKE {$currentPrefix}%");

        $update = $this->executeQuery(
            "UPDATE `{$usermetaTable}` SET `meta_key` = replace(meta_key, %s, %s) WHERE `meta_key` LIKE %s",
            $currentPrefix,
            $stagingPrefix,
            $currentPrefix . "%"
        );

        if ($update === false) {
            $this->logger->error("Failed to update database prefix in meta_key of {$usermetaTable}. Error: {$this->lastError()}");
        }

        $this->logger->info("Database prefix successfully updated from `{$currentPrefix}` to `{$stagingPrefix}` in {$usermetaTable}.");
        return $this->generateResponse();
    }
}
