<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use RuntimeException;
use WPStaging\Backup\Task\RestoreTask;

/**
 * @todo register analytics event and cleaning here
 */
class StartRestoreTask extends RestoreTask
{
    public static function getTaskName()
    {
        return 'backup_start_restore';
    }

    public static function getTaskTitle()
    {
        return 'Starting Restore';
    }

    public function execute()
    {
        if (!$this->stepsDto->getTotal()) {
            // The only requirement checking that really needs a step is the free disk space one, all other happens instantly.
            $this->stepsDto->setTotal(1);
        }

        try {
            $this->logger->info('#################### Start Restore Job ####################');
            $this->logger->writeLogHeader();
            $this->logger->info('Is Same Site Restore: ' . ($this->jobDataDto->getIsSameSiteBackupRestore() ? 'Yes' : 'No'));
        } catch (RuntimeException $e) {
            $this->logger->critical($e->getMessage());

            $this->jobDataDto->setRequirementFailReason($e->getMessage());

            return $this->generateResponse(false);
        }

        return $this->generateResponse();
    }
}
