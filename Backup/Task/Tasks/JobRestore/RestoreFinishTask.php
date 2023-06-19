<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use RuntimeException;
use WPStaging\Backup\Task\RestoreTask;

/**
 * @todo register analytics event and cleaning here
 */
class RestoreFinishTask extends RestoreTask
{
    public static function getTaskName()
    {
        return 'backup_restore_finish';
    }

    public static function getTaskTitle()
    {
        return 'Finishing Restore';
    }

    public function execute()
    {
        if (!$this->stepsDto->getTotal()) {
            // The only requirement checking that really needs a step is the free disk space one, all other happens instantly.
            $this->stepsDto->setTotal(1);
        }

        try {
            $this->logger->info("################## FINISH ##################");
        } catch (RuntimeException $e) {
            $this->logger->critical($e->getMessage());

            return $this->generateResponse(false);
        }

        return $this->generateResponse();
    }
}
