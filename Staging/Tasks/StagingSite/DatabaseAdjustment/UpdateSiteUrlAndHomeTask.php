<?php

namespace WPStaging\Staging\Tasks\StagingSite\DatabaseAdjustment;

use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Staging\Tasks\DatabaseAdjustmentTask;

/**
 * Partial Replacement for WPStaging\Framework\CloningProcess\Data\UpdateSiteUrlAndHome
 * This doesn't handle any multisite specific logic
 */
class UpdateSiteUrlAndHomeTask extends DatabaseAdjustmentTask
{
    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'staging_update_site_url_and_home';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Update site URL and home';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute()
    {
        $this->setup();
        $this->updateOptionsTable($this->jobDataDto->getStagingSiteUrl());

        return $this->generateResponse();
    }

    protected function updateOptionsTable(string $siteUrl): bool
    {
        $optionsTableName = $this->getOptionsTableName();
        $this->logger->info("Updating site url and home in {$optionsTableName}.");
        if ($this->isTableExcluded('usermeta')) {
            $this->logger->warning("Table {$optionsTableName} is excluded. Skipping.");
            return true;
        }

        $update = $this->executeQuery(
            "UPDATE {$optionsTableName} SET option_value = %s WHERE option_name = 'siteurl' or option_name='home'",
            $siteUrl
        );

        if ($update === false) {
            $this->logger->error("Failed to update site url and home in {$optionsTableName}. Error: {$this->lastError()}");
            return false;
        }

        $this->logger->info("Successfully updated site url and home in {$optionsTableName}.");
        return true;
    }
}
