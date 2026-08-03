<?php

namespace WPStaging\Basic\Ajax;

use WPStaging\Backup\BackupScheduler;
use WPStaging\Core\Cron\Cron;
use WPStaging\Framework\Security\Auth;

class ProCronsCleaner
{
    /** @var Cron */
    private $cronAdapter;

    /** @var BackupScheduler */
    private $backupScheduler;

    /** @var Auth */
    private $auth;

    public function __construct(Cron $cronAdapter, BackupScheduler $backupScheduler, Auth $auth)
    {
        $this->cronAdapter     = $cronAdapter;
        $this->backupScheduler = $backupScheduler;
        $this->auth            = $auth;
    }

    public function ajaxCleanProCrons()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            wp_send_json_error(['message' => esc_html__('Invalid Request!', 'wp-staging')], 401);
        }

        $proCrons        = $this->cronAdapter->getProEvents();
        $backupSchedules = $this->backupScheduler->getSchedules();

        foreach ($backupSchedules as $backupSchedule) {
            if ($this->isProCronSchedule($backupSchedule, $proCrons)) {
                $this->backupScheduler->deleteSchedule($backupSchedule['scheduleId'], $reCreateCron = false);
            }
        }

        $this->backupScheduler->reCreateCron();

        wp_send_json([
            'success' => true,
            'message' => esc_html__('Successfully removed PRO cron events.', 'wp-staging'),
        ]);
    }

    /**
     * @return bool
     */
    public function haveProCrons()
    {
        $proCrons        = $this->cronAdapter->getProEvents();
        $backupSchedules = $this->backupScheduler->getSchedules();

        foreach ($backupSchedules as $backupSchedule) {
            if ($this->isProCronSchedule($backupSchedule, $proCrons)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $schedule
     * @param array $proCrons
     * @return bool
     */
    protected function isProCronSchedule($schedule, $proCrons)
    {
        return in_array($schedule['schedule'], $proCrons);
    }
}
