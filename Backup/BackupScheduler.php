<?php

namespace WPStaging\Backup;

use DateTime;
use WPStaging\Backup\BackgroundProcessing\Backup\PrepareBackup;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Core\Cron\Cron;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\Nonce;
use WPStaging\Notifications\Notifications;

use function WPStaging\functions\debug_log;














class BackupScheduler
{
 
    const OPTION_BACKUP_SCHEDULE_ERROR_REPORT = 'wpstg_backup_schedules_send_error_report';

 
    const OPTION_BACKUP_SCHEDULE_WARNING_REPORT = 'wpstg_backup_schedules_send_warning_report';

 
    const OPTION_BACKUP_SCHEDULE_GENERAL_REPORT = 'wpstg_backup_schedules_send_general_report';

 
    const OPTION_BACKUP_SCHEDULE_SLACK_ERROR_REPORT = 'wpstg_backup_schedules_send_slack_error_report';

 
    const OPTION_BACKUP_SCHEDULE_REPORT_SLACK_WEBHOOK = 'wpstg_backup_schedules_report_slack_webhook';

 
    const OPTION_BACKUP_SCHEDULES = 'wpstg_backup_schedules';

 
    const OPTION_LAST_BACKUP_FAILURE = 'wpstg_last_backup_failure';

 
    const CRON_WARNING_TYPE_FAILURE = 'failure';

 
    const CRON_WARNING_TYPE_OVERDUE = 'overdue';

 
    const OVERDUE_GRACE_PERIOD = 30 * MINUTE_IN_SECONDS;

 
    const TRANSIENT_BACKUP_SCHEDULE_ERROR_REPORT_SENT = 'wpstg.backup.schedules.error_report_sent';

 
    const TRANSIENT_BACKUP_SCHEDULE_WARNING_REPORT_SENT = 'wpstg.backup.schedules.warning_report_sent';

 
    const TRANSIENT_BACKUP_SCHEDULE_GENERAL_REPORT_SENT = 'wpstg.backup.schedules.general_report_sent';

 
    const TRANSIENT_BACKUP_SCHEDULE_SLACK_REPORT_SENT = 'wpstg.backup.schedules.slack_report_sent';

 
    const REPORT_TYPE_ERROR = 'error';

 
    const REPORT_TYPE_WARNING = 'warning';

 
    const REPORT_TYPE_GENERAL = 'general';

 
    const FILTER_SCHEDULES_BACKUP_INTERVAL = 'wpstg.schedulesBackup.interval';

 
    protected $backupsFinder;

 
    protected $processLock;

 
    protected $backupDeleter;




    protected $notifications;

 
    protected $numberOverdueCronjobs = 0;





    protected $cronWarningType = '';





    protected $lastBackupFailureMessage = '';







    public function __construct(BackupsFinder $backupsFinder, ProcessLock $processLock, BackupDeleter $backupDeleter, Notifications $notifications)
    {
        $this->backupsFinder = $backupsFinder;
        $this->processLock   = $processLock;
        $this->backupDeleter = $backupDeleter;
        $this->notifications = $notifications;

        $this->countOverdueCronjobs();
    }




    public function getSchedules(): array
    {
        $schedules = get_option(static::OPTION_BACKUP_SCHEDULES, []);
        if (is_array($schedules)) {
            return $schedules;
        }

        return [];
    }





    public function maybeDeleteOldBackups(JobBackupDataDto $jobBackupDataDto)
    {
        $scheduleId = $jobBackupDataDto->getScheduleId();

 
        if (empty($scheduleId)) {
            return;
        }

        $schedules = get_option(static::OPTION_BACKUP_SCHEDULES, []);

        $schedule = array_filter($schedules, function ($schedule) use ($scheduleId) {
            return $schedule['scheduleId'] == $scheduleId;
        });

        if (empty($schedule)) {
            debug_log("Could not delete old backups for schedule ID $scheduleId as the schedule rotation plan was not found in the database.");
            return;
        }

        $schedule = array_shift($schedule);

        $maxAllowedBackupFiles = absint($schedule['rotation']);

        $backupFiles = $this->backupsFinder->findBackupByScheduleId($scheduleId);

 
        if (count($backupFiles) < $maxAllowedBackupFiles) {
            return;
        }

 
        uasort($backupFiles, function ($backup1, $backup2) {




            if ($backup1->getMTime() === $backup2->getMTime()) {
                return 0;
            }

            return $backup1->getMTime() < $backup2->getMTime() ? -1 : 1;
        });

 
        $backupFiles = array_values($backupFiles);

 
        $backupFiles = array_slice($backupFiles, 0, max(1, count($backupFiles) - $maxAllowedBackupFiles + 1));

        array_map(function ($file) {
            $this->backupDeleter->clearErrors();
            $this->backupDeleter->deleteBackup($file);
            $errors = $this->backupDeleter->getErrors();
            foreach ($errors as $error) {
                debug_log('Tried to cleanup old backups for backup plan rotation, but couldn\'t delete file: ' . $error);
            }
        }, $backupFiles);
    }







    public function scheduleBackup(JobBackupDataDto $jobBackupDataDto, string $scheduleId)
    {
        if (!isset(wp_get_schedules()[$jobBackupDataDto->getScheduleRecurrence()])) {
            debug_log("Tried to schedule a backup, but schedule '" . $jobBackupDataDto->getScheduleRecurrence() . "' is not registered as a WordPress cron schedule. Data DTO: " . wp_json_encode($jobBackupDataDto));

            return;
        }

        $firstSchedule = new \DateTime('now', wp_timezone());
        $time          = $jobBackupDataDto->getScheduleTime();
        $recurrence    = $jobBackupDataDto->getScheduleRecurrence();
        $dayOfWeek     = Cron::extractDayFromSchedule($recurrence);
        $this->setUpcomingDateTime($firstSchedule, $time, $dayOfWeek, $recurrence);

        $backupSchedule = [
            'scheduleId'                     => $scheduleId,
            'schedule'                       => $jobBackupDataDto->getScheduleRecurrence(),
            'backupType'                     => $jobBackupDataDto->getBackupType(),
            'subsiteBlogId'                  => $jobBackupDataDto->getSubsiteBlogId(), 
            'time'                           => $time,
            'name'                           => $jobBackupDataDto->getName(),
            'rotation'                       => $jobBackupDataDto->getScheduleRotation(),
            'isExportingPlugins'             => $jobBackupDataDto->getIsExportingPlugins(),
            'isExportingMuPlugins'           => $jobBackupDataDto->getIsExportingMuPlugins(),
            'isExportingThemes'              => $jobBackupDataDto->getIsExportingThemes(),
            'isExportingUploads'             => $jobBackupDataDto->getIsExportingUploads(),
            'isExportingOtherWpContentFiles' => $jobBackupDataDto->getIsExportingOtherWpContentFiles(),
            'isExportingOtherWpRootFiles'    => $jobBackupDataDto->getIsExportingOtherWpRootFiles(),
            'isExportingDatabase'            => $jobBackupDataDto->getIsExportingDatabase(),
            'sitesToBackup'                  => $jobBackupDataDto->getSitesToBackup(),
            'storages'                       => $jobBackupDataDto->getStorages(),
            'firstSchedule'                  => $firstSchedule->getTimestamp(),
            'isSmartExclusion'               => $jobBackupDataDto->getIsSmartExclusion(),
            'isExcludingSpamComments'        => $jobBackupDataDto->getIsExcludingSpamComments(),
            'isExcludingPostRevision'        => $jobBackupDataDto->getIsExcludingPostRevision(),
            'isExcludingDeactivatedPlugins'  => $jobBackupDataDto->getIsExcludingDeactivatedPlugins(),
            'isExcludingUnusedThemes'        => $jobBackupDataDto->getIsExcludingUnusedThemes(),
            'isExcludingLogs'                => $jobBackupDataDto->getIsExcludingLogs(),
            'isExcludingCaches'              => $jobBackupDataDto->getIsExcludingCaches(),
            'isWpCliRequest'                 => true, 
            'backupExcludedDirectories'      => $jobBackupDataDto->getBackupExcludedDirectories(),
        ];

        if (wp_next_scheduled(Cron::ACTION_CREATE_CRON_BACKUP, [$backupSchedule])) {
            debug_log('[Schedule Backup Cron] Early bailed when registering the cron to create a backup on a schedule, because it already exists');

            return;
        }

        $this->registerScheduleInDb($backupSchedule);
        $this->reCreateCron();
    }






    protected function registerScheduleInDb(array $backupSchedule): bool
    {
        $backupSchedules = get_option(static::OPTION_BACKUP_SCHEDULES, []);
        if (!is_array($backupSchedules)) {
            $backupSchedules = [];
        }

        $backupSchedules[] = $backupSchedule;

        if (!update_option(static::OPTION_BACKUP_SCHEDULES, $backupSchedules, false)) {
            debug_log('[Schedule Backup Cron] Could not update BackupSchedules DB option');
            return false;
        }

        return true;
    }







    public function createCronBackup(array $backupData)
    {
 
        $logId = wp_generate_password(4, false);

        debug_log(sprintf("[Schedule Backup Cron - %s] Received request to create a backup using Cron. Backup Data: %s", $logId, wp_json_encode($backupData)), 'info', false);

        try {
            debug_log(sprintf("[Schedule Backup Cron - %s] Preparing job", $logId), 'info', false);
            $jobId = WPStaging::make(PrepareBackup::class)->prepare($backupData);
            if ($jobId instanceof \WP_Error) {
                debug_log(sprintf("[Schedule Backup Cron - %s] Failed to create backup: %s", $logId, $jobId->get_error_message()));
                $this->saveBackupFailure($jobId->get_error_message());
                return;
            }

            debug_log(sprintf("[Schedule Backup Cron - %s] Successfully received a Job ID: %s", $logId, $jobId), 'info', false);
        } catch (\Exception $e) {
            debug_log("[Schedule Backup Cron - $logId] Exception thrown while preparing the Backup: " . $e->getMessage());
            $this->saveBackupFailure($e->getMessage());
        }
    }





    public function dismissSchedule()
    {
        if (!current_user_can((new Capabilities())->manageWPSTG())) {
            return;
        }

        if (!(new Nonce())->requestHasValidNonce(Nonce::WPSTG_NONCE)) {
            return;
        }

        if (empty($_POST['scheduleId'])) {
            return;
        }

        try {
            $this->deleteSchedule(Sanitize::sanitizeString($_POST['scheduleId']));
            wp_send_json_success();
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }







    public function deleteSchedule(string $scheduleId, $reCreateCron = true)
    {
        $schedules = $this->getSchedules();

        $newSchedules = array_filter($schedules, function ($schedule) use ($scheduleId) {
            return $schedule['scheduleId'] != $scheduleId;
        });

        if (!update_option(static::OPTION_BACKUP_SCHEDULES, $newSchedules, false)) {
            debug_log('[Schedule Backup Cron] Could not update BackupSchedules DB option after removing schedule.');
            throw new \RuntimeException('Could not unschedule event from Db.');
        }

 
 
 
        if (empty($newSchedules)) {
            delete_option(static::OPTION_LAST_BACKUP_FAILURE);
        }

        if ($reCreateCron === false) {
            return;
        }

        $this->reCreateCron();
    }














    public function reCreateCron($scheduleBeingEdit = null): bool
    {
        $schedules = $this->getSchedules();
        static::removeBackupSchedulesFromCron();

        $errors = [];

        foreach ($schedules as $schedule) {
            $timeToSchedule = new \DateTime('now', wp_timezone());




            if (isset(wp_get_schedules()[$schedule['schedule']]) && isset($schedule['firstSchedule']) && ($schedule['scheduleId'] !== $scheduleBeingEdit)) {
                $this->setNextSchedulingDate($timeToSchedule, $schedule);
            } else {
                $dayOfWeek = Cron::extractDayFromSchedule($schedule['schedule']);
                $this->setUpcomingDateTime($timeToSchedule, $schedule['time'], $dayOfWeek, $schedule['schedule']);
            }

 
            $result = wp_schedule_event($timeToSchedule->format('U'), $schedule['schedule'], Cron::ACTION_CREATE_CRON_BACKUP, [$schedule]);

 
 
            if ($result === false || $result instanceof \WP_Error) {
                if ($result instanceof \WP_Error) {
                    $details = $result->get_error_message();
                } else {
                    $details = '';
                }

                $error = '[Schedule Backup Cron] Failed to register the cron event wpstg_create_cron_backup. ' . $schedule['schedule'] . ' ' . $details;

                $errors[] = $error;

                debug_log($error);
            }
        }

        if (!empty($errors)) {
            return false;
        }

        return true;
    }









    public function reCreateCronIfSchedulesExist(): bool
    {
        if (empty($this->getSchedules())) {
            return true;
        }

        return $this->reCreateCron();
    }










    public static function removeBackupSchedulesFromCron(): bool
    {
        $cron = get_option('cron');

 
        if (!is_array($cron)) {
            return false;
        }

 
        foreach ($cron as $timestamp => &$events) {
            if (is_array($events)) {
                foreach ($events as $callback => &$args) {
                    if ($callback === Cron::ACTION_CREATE_CRON_BACKUP) {
                        unset($cron[$timestamp][$callback]);
                    }
                }
            }
        }

 
 
 
        $cron = array_filter($cron, function ($timestamps) {
            return !empty($timestamps);
        });

        update_option('cron', $cron);

        return true;
    }








    public function checkCronStatus(): bool
    {
        $this->cronWarningType          = '';
        $this->lastBackupFailureMessage = '';

        if ($this->isSchedulesEmpty()) {
            return true;
        }

        $this->detectScheduledBackupWarning();

        return $this->cronWarningType === '';
    }






    private function detectScheduledBackupWarning()
    {
        $lastFailure = get_option(self::OPTION_LAST_BACKUP_FAILURE);
        if (is_array($lastFailure) && !empty($lastFailure['time']) && (int)$lastFailure['time'] > $this->getLastScheduledBackupSuccessTime()) {
            $this->cronWarningType          = self::CRON_WARNING_TYPE_FAILURE;
            $this->lastBackupFailureMessage = $lastFailure['message'] ?? '';
            return;
        }

        if ($this->hasOverdueOrMissingBackupCronJob()) {
            $this->cronWarningType = self::CRON_WARNING_TYPE_OVERDUE;
        }
    }






    private function getLastScheduledBackupSuccessTime(): int
    {
        $lastBackupInfo = $this->getLastBackupInfo();
        if (empty($lastBackupInfo['endTime'])) {
            return 0;
        }

        $jobDataDto = isset($lastBackupInfo['JobBackupDataDto']) ? $lastBackupInfo['JobBackupDataDto'] : null;
        if (!($jobDataDto instanceof JobBackupDataDto) || !$jobDataDto->isScheduledBackup()) {
            return 0;
        }

        return (int)$lastBackupInfo['endTime'];
    }




    private function getLastBackupInfo(): array
    {
        $lastBackupInfo = get_option(FinishBackupTask::OPTION_LAST_BACKUP, []);

        return is_array($lastBackupInfo) ? $lastBackupInfo : [];
    }

 
    public function getOverdueCronJobsCount(): int
    {
        return $this->numberOverdueCronjobs;
    }

 
    public function isWpCronDisabled(): bool
    {
        return defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
    }

 
    public function hasOverdueCronJobs(): bool
    {
        return $this->numberOverdueCronjobs > 4;
    }




    public function getWarningType(): string
    {
        return $this->cronWarningType;
    }




    public function getLastBackupFailureMessage(): string
    {
        return $this->lastBackupFailureMessage;
    }





    public function getNextBackupSchedule(): array
    {
        $cron = get_option('cron');

 
        if (!is_array($cron)) {
            throw new \UnexpectedValueException();
        }

        ksort($cron, SORT_NUMERIC);

 
        foreach ($cron as $timestamp => &$events) {
            if (is_array($events)) {
                foreach ($events as $callback => &$args) {
                    if ($callback === Cron::ACTION_CREATE_CRON_BACKUP) {
                        return [$timestamp, $cron[$timestamp][$callback]];
                    }
                }
            }
        }

 
        throw new \OutOfBoundsException();
    }










    protected function setUpcomingDateTime(DateTime &$datetime, $time, $dayOfWeek = null, $scheduleRecurrence = null)
    {
        if (is_array($time)) {
            $hourAndMinute = $time;
        } else {
            $hourAndMinute = explode(':', $time);
        }

 
        $isWeeklySchedule = $scheduleRecurrence === Cron::WEEKLY ||
                           $scheduleRecurrence === Cron::EVERY_TWO_WEEKS ||
                           strpos($scheduleRecurrence, Cron::WEEKLY . '_') === 0;

        if ($dayOfWeek !== null && $isWeeklySchedule) {
 
 
            $currentDayOfWeek = (int)$datetime->format('N');
            $targetDayOfWeek  = (int)$dayOfWeek;

 
            $targetTimeInt    = (int) sprintf('%02d%02d', $hourAndMinute[0], $hourAndMinute[1]);
            $currentTimeInt   = (int) $datetime->format('Hi');
            $daysUntilTarget  = $targetDayOfWeek - $currentDayOfWeek;

            if ($daysUntilTarget < 0) {
                $daysUntilTarget += 7;
            }

 
            if ($daysUntilTarget === 0 && $targetTimeInt <= $currentTimeInt) {
                $daysUntilTarget = 7;
            }

 
            if ($daysUntilTarget > 0) {
                $datetime->add(new \DateInterval("P{$daysUntilTarget}D"));
            }
        } else {
 
            if ((int)sprintf('%02d%02d', $hourAndMinute[0], $hourAndMinute[1]) <= (int)$datetime->format('Hi')) {
                $datetime->add(new \DateInterval('P1D'));
            }
        }

        $datetime->setTime($hourAndMinute[0], $hourAndMinute[1]);
    }








    protected function setNextSchedulingDate(DateTime &$datetime, array $schedule)
    {
        $next = $schedule['firstSchedule'];
        $now  = $datetime->getTimestamp();
        if ($next >= $now) {
            $dayOfWeek = Cron::extractDayFromSchedule($schedule['schedule']);
            $this->setUpcomingDateTime($datetime, $schedule['time'], $dayOfWeek, $schedule['schedule']);
            return;
        }

        $recurrance = wp_get_schedules()[$schedule['schedule']];
        while ($next < $now) {
            $next += $recurrance['interval'];
        }

        $datetime->setTimestamp($next);
    }










    public function sendErrorReport(string $message, string $title = ''): bool
    {
        if (get_option(self::OPTION_BACKUP_SCHEDULE_ERROR_REPORT) !== 'true') {
            return false;
        }

        if (empty($message)) {
            return false;
        }

        if (strpos($message, 'index resource') !== false) {
            $message .= "\r\n \r\n" . esc_html__("This can happen if another process deleted the backup while it was created. Please report this to support@wp-staging.com if it happens often. Otherwise you can ignore it.", 'wp-staging');
        }

        if (empty($title)) {
            $title = esc_html__('WP Staging - Backup Error Report', 'wp-staging');
        }

        $this->sendEmailReport($message, $title);
        $this->sendSlackReport($message, $title);

        return true;
    }










    public function sendWarningReport(string $message, string $title = ''): bool
    {
        if (get_option(self::OPTION_BACKUP_SCHEDULE_WARNING_REPORT) !== 'true') {
            return false;
        }

        if (empty($message)) {
            return false;
        }

        if (empty($title)) {
            $title = esc_html__('WP Staging - Backup Warning Report', 'wp-staging');
        }

        $this->sendEmailReport($message, $title, self::REPORT_TYPE_WARNING);

        return true;
    }










    public function sendGeneralReport(string $message, string $title = ''): bool
    {
        if (get_option(self::OPTION_BACKUP_SCHEDULE_GENERAL_REPORT) !== 'true') {
            return false;
        }

        if (empty($message)) {
            return false;
        }

        if (empty($title)) {
            $title = esc_html__('WP Staging - Backup General Report', 'wp-staging');
        }

        $this->sendEmailReport($message, $title, self::REPORT_TYPE_GENERAL);

        return true;
    }









    public function sendEmailReport(string $message, string $title = '', string $reportType = self::REPORT_TYPE_ERROR): bool
    {
        $optionName = $this->getReportOptionName($reportType);

        if (get_option($optionName) !== 'true') {
            return false;
        }

        $reportEmail = get_option(Notifications::OPTION_BACKUP_SCHEDULE_REPORT_EMAIL);
        if (!filter_var($reportEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

 
        $transientName = $this->getReportTransientName($reportType);
        if (get_transient($transientName) !== false) {
            return false;
        }

        if (empty($message)) {
            return false;
        }

        if (empty($title)) {
            $title = $this->getDefaultReportTitle($reportType);
        }

 
        $transientName = $this->getReportTransientName($reportType);
        set_transient($transientName, true, 5 * 60);

        if (get_option(Notifications::OPTION_SEND_EMAIL_AS_HTML, false) === 'true') {
            return $this->notifications->sendEmailAsHTML($reportEmail, $title, $message);
        }

        return $this->notifications->sendEmail($reportEmail, $title, $message);
    }









    public function sendSlackReport(string $message, string $title = ''): bool
    {
        if (!WPStaging::isPro()) {
            return false;
        }

        if (get_option(self::OPTION_BACKUP_SCHEDULE_SLACK_ERROR_REPORT) !== 'true') {
            return false;
        }

        $webhook = get_option(self::OPTION_BACKUP_SCHEDULE_REPORT_SLACK_WEBHOOK);
        if (!filter_var($webhook, FILTER_VALIDATE_URL)) {
            return false;
        }

 
        if (get_transient(self::TRANSIENT_BACKUP_SCHEDULE_SLACK_REPORT_SENT) !== false) {
            return false;
        }

        if (empty($message)) {
            return false;
        }

        if (empty($title)) {
            $title = esc_html__('WP Staging - Backup Report', 'wp-staging');
        }

 
        set_transient(self::TRANSIENT_BACKUP_SCHEDULE_SLACK_REPORT_SENT, true, 5 * 60);
        return $this->notifications->sendSlack($webhook, $title, $message);
    }







    private function getReportOptionName(string $reportType): string
    {
        switch ($reportType) {
            case self::REPORT_TYPE_WARNING:
                return self::OPTION_BACKUP_SCHEDULE_WARNING_REPORT;
            case self::REPORT_TYPE_GENERAL:
                return self::OPTION_BACKUP_SCHEDULE_GENERAL_REPORT;
            default:
                return self::OPTION_BACKUP_SCHEDULE_ERROR_REPORT;
        }
    }







    private function getReportTransientName(string $reportType): string
    {
        switch ($reportType) {
            case self::REPORT_TYPE_WARNING:
                return self::TRANSIENT_BACKUP_SCHEDULE_WARNING_REPORT_SENT;
            case self::REPORT_TYPE_GENERAL:
                return self::TRANSIENT_BACKUP_SCHEDULE_GENERAL_REPORT_SENT;
            case self::REPORT_TYPE_ERROR:
            default:
                return self::TRANSIENT_BACKUP_SCHEDULE_ERROR_REPORT_SENT;
        }
    }







    private function getDefaultReportTitle(string $reportType): string
    {
        switch ($reportType) {
            case self::REPORT_TYPE_WARNING:
                return esc_html__('WP Staging - Backup Warning Report', 'wp-staging');
            case self::REPORT_TYPE_GENERAL:
                return esc_html__('WP Staging - Backup General Report', 'wp-staging');
            default:
                return esc_html__('WP Staging - Backup Error Report', 'wp-staging');
        }
    }




    private function isSchedulesEmpty(): bool
    {
        $schedules = get_option(static::OPTION_BACKUP_SCHEDULES, []);
        if (empty($schedules)) {
            return true;
        }

        return false;
    }




    private function getCronJobs(): array
    {
        $cron = get_option('cron');
        if (!is_array($cron)) {
            return [];
        }

        $cronJobs = [];
        foreach ($cron as $timestamp => $hooks) {
            if (!is_numeric($timestamp) || !is_array($hooks)) {
                continue;
            }

            $cronJobs[(int)$timestamp] = $hooks;
        }

        return $cronJobs;
    }




    private function countOverdueCronjobs()
    {
        $cronJobs = $this->getCronJobs();
        $timeNow  = time();
        foreach ($cronJobs as $expectedExecutionTime => $cronJob) {
            if (($expectedExecutionTime + self::OVERDUE_GRACE_PERIOD) < $timeNow) {
                $this->numberOverdueCronjobs++;
            }
        }
    }









    public function onBackgroundJobFailure(array $args)
    {
        $jobDataDto = isset($args['jobDataDto']) ? $args['jobDataDto'] : null;
        if (!($jobDataDto instanceof JobBackupDataDto)) {
            return;
        }

        if (!$jobDataDto->isScheduledBackup()) {
            return;
        }

        $errorMessage = isset($args['errorMessage']) ? (string)$args['errorMessage'] : '';
        $this->saveBackupFailure($errorMessage);
    }





    private function saveBackupFailure(string $message)
    {
        update_option(self::OPTION_LAST_BACKUP_FAILURE, [
            'time'    => time(),
            'message' => $message,
        ], false);
    }








    private function hasOverdueOrMissingBackupCronJob(): bool
    {
        $eventExists = false;
        $now         = time();

        foreach ($this->getCronJobs() as $timestamp => $hooks) {
            if (!isset($hooks[Cron::ACTION_CREATE_CRON_BACKUP])) {
                continue;
            }

            $eventExists = true;
            if (($timestamp + self::OVERDUE_GRACE_PERIOD) < $now) {
                return true;
            }
        }

        return !$eventExists;
    }
}
