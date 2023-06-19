<?php

namespace WPStaging\Basic\Ajax;

use WPStaging\Backup\BackupScheduler;
use WPStaging\Core\Cron\Cron;

class ProCronsCleaner
{
    /** @var Cron */
    private $cronAdapter;

    /** @var BackupScheduler */
    private $backupScheduler;

    public function __construct(Cron $cronAdapter, BackupScheduler $backupScheduler)
    {
        $this->cronAdapter     = $cronAdapter;
        $this->backupScheduler = $backupScheduler;
    }

    public function ajaxCleanProCrons()
    {
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
