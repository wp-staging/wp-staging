<?php

namespace WPStaging\Staging\Tasks\StagingSiteReset;

use RuntimeException;
use WPStaging\Staging\Tasks\StagingSiteUpdate\UpdateRequirementsCheckTask;

class ResetRequirementsCheckTask extends UpdateRequirementsCheckTask
{
    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'staging_site_reset_requirements_check';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Requirements Check';
    }

    /**
     * @return void
     */
    protected function logStartHeader()
    {
        $this->logger->info('#################### Start Staging Site Reset Job ####################');
    }

    /**
     * @return void
     */
    protected function logRequirementsCheckPassed()
    {
        $this->logger->info('Staging Site reset requirements passed...');
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function cannotUpdateIfStagingSiteNoExists()
    {
        $stagingSitePath = $this->jobDataDto->getStagingSitePath();
        if (!is_dir($stagingSitePath)) {
            throw new RuntimeException(esc_html__('Cannot reset staging site. Staging site directory does not exist!', 'wp-staging'));
        }
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function cannotUpdateIfUsingExternalDatabase()
    {
        if ($this->jobDataDto->getIsExternalDatabase()) {
            throw new RuntimeException(esc_html__('Staging site reset with external database is not supported in the basic version.', 'wp-staging'));
        }
    }
}
