<?php

namespace WPStaging\Basic\Ajax;

use WPStaging\Backup\BackupScheduler;
use WPStaging\Core\Cron\Cron;
use WPStaging\Framework\Security\Auth;

class ProCronsCleaner
{
 
    private $backupScheduler;

 
    private $auth;

    public function __construct(BackupScheduler $backupScheduler, Auth $auth)
    {
        $this->backupScheduler = $backupScheduler;
        $this->auth            = $auth;
    }

    public function ajaxCleanProCrons()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            wp_send_json_error(['message' => esc_html__('Invalid Request!', 'wp-staging')], 401);
        }

        foreach ($this->backupScheduler->getSchedules() as $backupSchedule) {
            if ($this->scheduleWasCreatedByPro($backupSchedule)) {
                $this->backupScheduler->deleteSchedule($backupSchedule['scheduleId'], $reCreateCron = false);
            }
        }

        $this->backupScheduler->reCreateCron();

        wp_send_json([
            'success' => true,
            'message' => esc_html__('Successfully removed PRO cron events.', 'wp-staging'),
        ]);
    }




    public function haveProCrons()
    {
        foreach ($this->backupScheduler->getSchedules() as $backupSchedule) {
            if ($this->scheduleWasCreatedByPro($backupSchedule)) {
                return true;
            }
        }

        return false;
    }








    protected function scheduleWasCreatedByPro($schedule): bool
    {
        $recurrence = (string)($schedule['schedule'] ?? '');

        return $recurrence !== '' && $recurrence !== Cron::BASIC_DAILY;
    }
}
