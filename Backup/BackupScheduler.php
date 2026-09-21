<?php

namespace WPStaging\Backup;

use DateTime;
use WPStaging\Backup\BackgroundProcessing\Backup\PrepareBackup;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Core\Cron\Cron;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\Nonce;
use WPStaging\Backup\Storage\Providers;
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

 
    const TRANSIENT_SCHEDULE_JOB_PREFIX = 'wpstg_schedule_for_job_';

 
    const CRON_WARNING_TYPE_FAILURE = 'failure';

 
    const CRON_WARNING_TYPE_OVERDUE = 'overdue';

 
    const OVERDUE_GRACE_PERIOD = 30 * MINUTE_IN_SECONDS;

 
    const TRANSIENT_BACKUP_SCHEDULE_ERROR_REPORT_SENT = 'wpstg.backup.schedules.error_report_sent';

 
    const TRANSIENT_BACKUP_SCHEDULE_SLACK_REPORT_SENT = 'wpstg.backup.schedules.slack_report_sent';

 
    const CRON_SAVE_FAILURE_REPORTED_MARKER = '.cron-save-failure-reported';

 
    const REPORT_TYPE_ERROR = 'error';

 
    const REPORT_TYPE_WARNING = 'warning';

 
    const REPORT_TYPE_GENERAL = 'general';

 
    const FILTER_SCHEDULES_BACKUP_INTERVAL = 'wpstg.schedulesBackup.interval';

 
    const LAST_RUN_ERROR_MAX_LENGTH = 500;

 
    const FIELD_RUNS_RECORDED_SINCE = 'runsRecordedSince';

 
    protected $backupsFinder;

 
    protected $processLock;

 
    protected $backupDeleter;




    protected $notifications;





    protected $cronWarningType = '';





    protected $lastBackupFailureMessage = '';







    public function __construct(BackupsFinder $backupsFinder, ProcessLock $processLock, BackupDeleter $backupDeleter, Notifications $notifications)
    {
        $this->backupsFinder = $backupsFinder;
        $this->processLock   = $processLock;
        $this->backupDeleter = $backupDeleter;
        $this->notifications = $notifications;
    }




    public function getSchedules(): array
    {
        $schedules = get_option(static::OPTION_BACKUP_SCHEDULES, []);
        if (!is_array($schedules)) {
            return [];
        }

        $schedules = array_filter($schedules, 'is_array');

        foreach ($schedules as &$schedule) {
            if (!array_key_exists('lastRunTime', $schedule)) {
                $schedule['lastRunTime']     = null;
                $schedule['lastRunStatus']   = null;
                $schedule['lastRunDuration'] = null;
            }

            if (!array_key_exists('lastRunJobId', $schedule)) {
                $schedule['lastRunJobId'] = '';
                $schedule['lastRunError'] = '';
            }

            if (!array_key_exists('isPaused', $schedule)) {
                $schedule['isPaused'] = false;
            }
        }

        unset($schedule);

        return $schedules;
    }








    public function getNextRunTimestampsByScheduleId(): array
    {
        $cron   = get_option('cron');
        $result = [];

        if (!is_array($cron)) {
            return $result;
        }

        ksort($cron, SORT_NUMERIC);

        foreach ($cron as $timestamp => $events) {
            if (!is_array($events) || !isset($events[Cron::ACTION_CREATE_CRON_BACKUP])) {
                continue;
            }

            foreach ($events[Cron::ACTION_CREATE_CRON_BACKUP] as $event) {
                if (!isset($event['args'][0]['scheduleId'])) {
                    continue;
                }

                $sid = $event['args'][0]['scheduleId'];

                if (!isset($result[$sid])) {
                    $result[$sid] = (int)$timestamp;
                }
            }
        }

        return $result;
    }









    public function updateScheduleLastRun(string $scheduleId, string $status, int $duration = 0, string $errorMessage = '', string $jobId = '')
    {
        $schedules = $this->getSchedules();
        $updated   = false;

        foreach ($schedules as &$schedule) {
            if ($schedule['scheduleId'] === $scheduleId) {
                $schedule['lastRunTime']     = time();
                $schedule['lastRunStatus']   = $status;
                $schedule['lastRunDuration'] = $duration;
                $schedule['lastRunError']    = mb_substr($errorMessage, 0, self::LAST_RUN_ERROR_MAX_LENGTH);
                $schedule['lastRunJobId']    = $jobId;

                if ($status === 'failed' && !empty($errorMessage)) {
                    $storageKeys                       = isset($schedule['storages']) ? (array)$schedule['storages'] : [];
                    $schedule['reconnectStorageKeys']  = $this->detectAuthFailureStorageKeys($errorMessage, $storageKeys);
                } else {
                    $schedule['reconnectStorageKeys'] = [];
                }

                $updated = true;
                break;
            }
        }

        unset($schedule);

        if ($updated) {
            update_option(static::OPTION_BACKUP_SCHEDULES, $schedules, false);
        }
    }






    private function detectAuthFailureStorageKeys(string $errorMessage, array $storageKeys): array
    {
        $oauthProviders = [
            Providers::IDENTIFIER_GOOGLE_DRIVE,
            Providers::IDENTIFIER_DROPBOX,
            Providers::IDENTIFIER_ONE_DRIVE,
            Providers::IDENTIFIER_PCLOUD,
        ];

        $authErrorPatterns = [
            'reconnect',
            'fail to refresh the access token',
            'token expired',
            'access token expired',
            'not authenticated',
        ];

        $lowerError  = strtolower($errorMessage);
        $isAuthError = false;

        foreach ($authErrorPatterns as $pattern) {
            if (strpos($lowerError, $pattern) !== false) {
                $isAuthError = true;
                break;
            }
        }

        if (!$isAuthError) {
            return [];
        }

        $failed = [];
        foreach ($storageKeys as $key) {
            if (in_array($key, $oauthProviders, true)) {
                $failed[] = $key;
            }
        }

        return $failed;
    }








    public function clearReconnectStorageKeyForProvider(string $storageKey)
    {
        $schedules = $this->getSchedules();
        $updated   = false;

        foreach ($schedules as &$schedule) {
            $keys = $this->readReconnectStorageKeys($schedule);

            if (!in_array($storageKey, $keys, true)) {
                continue;
            }

            $schedule['reconnectStorageKeys'] = array_values(array_filter($keys, function ($k) use ($storageKey) {
                return $k !== $storageKey;
            }));
            $updated = true;
        }

        unset($schedule);

        if ($updated) {
            update_option(static::OPTION_BACKUP_SCHEDULES, $schedules, false);
        }
    }





    private function readReconnectStorageKeys(array $schedule): array
    {
        return is_array($schedule['reconnectStorageKeys'] ?? null) ? $schedule['reconnectStorageKeys'] : [];
    }





    public function handleJobFailed(array $jobData)
    {
        if (empty($jobData['queueId'])) {
            return;
        }

        $transientKey = self::TRANSIENT_SCHEDULE_JOB_PREFIX . $jobData['queueId'];
        $scheduleId   = get_transient($transientKey);

        if (empty($scheduleId)) {
            return;
        }

        delete_transient($transientKey);
        $errorMessage = isset($jobData['message']) ? (string)$jobData['message'] : '';
        $jobId        = isset($jobData['jobId']) ? (string)$jobData['jobId'] : '';
        $this->updateScheduleLastRun($scheduleId, 'failed', 0, $errorMessage, $jobId);
    }




    public function getSchedulesRunByCurrentSite(): array
    {
        return array_values(array_filter($this->getSchedules(), [$this, 'scheduleIsRunByCurrentSite']));
    }





    private function findScheduleById(string $scheduleId)
    {
        if ($scheduleId === '') {
            return null;
        }

        foreach ($this->getSchedules() as $schedule) {
            if (isset($schedule['scheduleId']) && (string)$schedule['scheduleId'] === $scheduleId) {
                return $schedule;
            }
        }

        return null;
    }









    public function scheduleIsRunByCurrentSite(array $schedule): bool
    {
        if (!is_multisite()) {
            return true;
        }

        if (isset($schedule['ownerBlogId'])) {
            return (int)$schedule['ownerBlogId'] === get_current_blog_id();
        }

        if (is_main_site()) {
            return true;
        }

        return isset($schedule['subsiteBlogId']) && (int)$schedule['subsiteBlogId'] === get_current_blog_id();
    }








    public static function cronEventArguments(string $scheduleId): array
    {
        return ['scheduleId' => $scheduleId];
    }





    public function maybeDeleteOldBackups(JobBackupDataDto $jobBackupDataDto)
    {
        $scheduleId = $jobBackupDataDto->getScheduleId();

 
        if (empty($scheduleId)) {
            return;
        }

        $schedule = $this->findScheduleById((string)$scheduleId);

        if ($schedule === null) {
            debug_log("Could not delete old backups for schedule ID $scheduleId as the schedule rotation plan was not found in the database.");
            return;
        }

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

        $time          = $jobBackupDataDto->getScheduleTime();
        $recurrence    = $jobBackupDataDto->getScheduleRecurrence();
        $firstSchedule = $this->getUpcomingScheduleTime(implode(':', $time), $recurrence);

        $backupSchedule = [
            'scheduleId'                     => $scheduleId,
            'ownerBlogId'                    => get_current_blog_id(),
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
            'firstSchedule'                  => $firstSchedule,
            self::FIELD_RUNS_RECORDED_SINCE  => time(),
            'isSmartExclusion'               => $jobBackupDataDto->getIsSmartExclusion(),
            'isExcludingSpamComments'        => $jobBackupDataDto->getIsExcludingSpamComments(),
            'isExcludingPostRevision'        => $jobBackupDataDto->getIsExcludingPostRevision(),
            'isExcludingDeactivatedPlugins'  => $jobBackupDataDto->getIsExcludingDeactivatedPlugins(),
            'isExcludingUnusedThemes'        => $jobBackupDataDto->getIsExcludingUnusedThemes(),
            'isExcludingLogs'                => $jobBackupDataDto->getIsExcludingLogs(),
            'isExcludingCaches'              => $jobBackupDataDto->getIsExcludingCaches(),
            'isWpCliRequest'                 => true, 
            'backupExcludedDirectories'      => $jobBackupDataDto->getBackupExcludedDirectories(),
            'lastRunTime'                    => null,
            'lastRunStatus'                  => null,
            'lastRunDuration'                => null,
            'lastRunJobId'                   => '',
            'lastRunError'                   => '',
        ];

        if (wp_next_scheduled(Cron::ACTION_CREATE_CRON_BACKUP, [self::cronEventArguments($scheduleId)])) {
            debug_log('[Schedule Backup Cron] Early bailed when registering the cron to create a backup on a schedule, because it already exists');

            return;
        }

        $this->registerScheduleInDb($backupSchedule);
        $this->reCreateCron();
    }








    public function adoptRunsFromExistingBackups(): int
    {
        $schedules = $this->getSchedules();
        if (empty($schedules) === true) {
            return 0;
        }

        $lastRunByScheduleId = $this->lastBackupTimeByScheduleId();
        $now                 = time();
        $adopted             = 0;

        foreach ($schedules as &$schedule) {
            if (!empty($schedule[self::FIELD_RUNS_RECORDED_SINCE]) || !empty($schedule['lastRunTime'])) {
                continue;
            }

            $lastRun = $lastRunByScheduleId[(string)($schedule['scheduleId'] ?? '')] ?? 0;

            if ($lastRun > 0) {
                $schedule['lastRunTime']   = $lastRun;
                $schedule['lastRunStatus'] = 'success';
            }

            $schedule[self::FIELD_RUNS_RECORDED_SINCE] = $lastRun > 0 ? $lastRun : $now;
            $adopted++;
        }

        unset($schedule);

        if ($adopted > 0) {
            update_option(static::OPTION_BACKUP_SCHEDULES, $schedules, false);
        }

        return $adopted;
    }




    private function lastBackupTimeByScheduleId(): array
    {
        try {
            $backups = $this->backupsFinder->findBackups();
        } catch (\Throwable $e) {
            debug_log('[Schedule Backup Cron] Could not read the existing backups while adopting the runs of the stored plans: ' . $e->getMessage());

            return [];
        }

        $lastRuns = [];

        foreach ($backups as $backup) {
            try {
                $scheduleId = (string)(new BackupMetadata())->hydrateByFilePath($backup->getPathname())->getScheduleId();
            } catch (\Throwable $e) {
                continue;
            }

            if ($scheduleId === '') {
                continue;
            }

            $createdAt = (int)$backup->getMTime();

            if ($createdAt > ($lastRuns[$scheduleId] ?? 0)) {
                $lastRuns[$scheduleId] = $createdAt;
            }
        }

        return $lastRuns;
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









    public function createCronBackup(array $cronEventArguments)
    {
 
        $logId      = wp_generate_password(4, false);
        $scheduleId = isset($cronEventArguments['scheduleId']) ? (string)$cronEventArguments['scheduleId'] : '';

        debug_log(sprintf("[Schedule Backup Cron - %s] Received request to create a backup using Cron. Schedule ID: %s", $logId, $scheduleId), 'info', false);

        $schedule = $this->findScheduleById($scheduleId);
        if ($schedule === null) {
            debug_log(sprintf("[Schedule Backup Cron - %s] Skipped: schedule %s no longer exists in the database.", $logId, $scheduleId), 'info', false);
            return;
        }

        if (!$this->scheduleIsRunByCurrentSite($schedule)) {
            debug_log(sprintf("[Schedule Backup Cron - %s] Skipped: schedule %s belongs to another site of the network, not site %d.", $logId, $scheduleId, get_current_blog_id()), 'info', false);
            return;
        }

        if ($this->scheduleHasQueuedOrRunningBackup($scheduleId)) {
            debug_log(sprintf("[Schedule Backup Cron - %s] Skipped: a backup job for schedule %s is already queued or running.", $logId, $scheduleId), 'info', false);
            return;
        }

        try {
            debug_log(sprintf("[Schedule Backup Cron - %s] Preparing job", $logId), 'info', false);
            $jobId = WPStaging::make(PrepareBackup::class)->prepare($schedule);
            if ($jobId instanceof \WP_Error) {
                debug_log(sprintf("[Schedule Backup Cron - %s] Failed to create backup: %s", $logId, $jobId->get_error_message()));
                $this->saveBackupFailure($jobId->get_error_message());
                return;
            }

            debug_log(sprintf("[Schedule Backup Cron - %s] Successfully received a Job ID: %s", $logId, $jobId), 'info', false);

            set_transient(self::TRANSIENT_SCHEDULE_JOB_PREFIX . $jobId, $scheduleId, 2 * DAY_IN_SECONDS);
        } catch (\Exception $e) {
            debug_log("[Schedule Backup Cron - $logId] Exception thrown while preparing the Backup: " . $e->getMessage());
            $this->saveBackupFailure($e->getMessage());
        }
    }









    public function reportCronSaveFailure($error, string $hook)
    {
        if ($hook !== Cron::ACTION_CREATE_CRON_BACKUP || !defined('WPSTG_DEBUG_LOG_FILE')) {
            return;
        }

        $marker = dirname(WPSTG_DEBUG_LOG_FILE) . '/' . self::CRON_SAVE_FAILURE_REPORTED_MARKER;
        if (file_exists($marker) && filemtime($marker) > time() - HOUR_IN_SECONDS) {
            return;
        }

        if (!touch($marker)) {
            return;
        }

        global $wpdb;

        debug_log(sprintf(
            '[Schedule Backup Cron] WordPress could not save the cron option on site %d (%s, error code: %s). Last database error: "%s". Cron option size: %d bytes.',
            get_current_blog_id(),
            current_action(),
            is_wp_error($error) ? $error->get_error_code() : 'unknown',
            $wpdb->last_error,
            strlen((string)maybe_serialize(get_option('cron')))
        ));
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








    private function findScheduleIndexById(array $schedules, string $scheduleId)
    {
        foreach ($schedules as $index => $schedule) {
            if ($schedule['scheduleId'] === $scheduleId) {
                return $index;
            }
        }

        return null;
    }





    public function pauseSchedule()
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

        $scheduleId = Sanitize::sanitizeString($_POST['scheduleId']);
        $schedules  = $this->getSchedules();
        $index      = $this->findScheduleIndexById($schedules, $scheduleId);

        if ($index === null) {
            wp_send_json_error('Schedule not found.');
            return;
        }

        $schedules[$index]['isPaused'] = true;

        update_option(static::OPTION_BACKUP_SCHEDULES, $schedules, false);
        $this->reCreateCron();
        wp_send_json_success();
    }





    public function resumeSchedule()
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

        $scheduleId = Sanitize::sanitizeString($_POST['scheduleId']);
        $schedules  = $this->getSchedules();
        $index      = $this->findScheduleIndexById($schedules, $scheduleId);

        if ($index === null) {
            wp_send_json_error('Schedule not found.');
            return;
        }

        $schedules[$index]['isPaused']                       = false;
        $schedules[$index]['firstSchedule']                  = $this->upcomingOccurrenceTimestamp($schedules[$index]);
        $schedules[$index][self::FIELD_RUNS_RECORDED_SINCE]   = time();

        update_option(static::OPTION_BACKUP_SCHEDULES, $schedules, false);
        $this->reCreateCron();
        wp_send_json_success();
    }









    private function upcomingOccurrenceTimestamp(array $schedule): int
    {
        $firstOccurrence = $this->firstOccurrenceTimestamp($schedule);
        $interval        = $this->intervalInSeconds($schedule);
        $now             = time();

        if ($firstOccurrence === null || $interval <= 0) {
            $recurrence = (string)($schedule['schedule'] ?? '');

            return $this->getUpcomingScheduleTime(implode(':', $this->scheduleTimeOfDay($schedule)), $recurrence);
        }

        if ($firstOccurrence >= $now) {
            return $firstOccurrence;
        }

        return $firstOccurrence + ((int)ceil(($now - $firstOccurrence) / $interval) * $interval);
    }





    private function scheduleTimeOfDay(array $schedule): array
    {
        if (empty($schedule['time'])) {
            return ['0', '0'];
        }

        return is_array($schedule['time']) ? $schedule['time'] : explode(':', (string)$schedule['time']);
    }





    public function runScheduleNow()
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

        $scheduleId = Sanitize::sanitizeString($_POST['scheduleId']);
        $schedules  = $this->getSchedules();
        $index      = $this->findScheduleIndexById($schedules, $scheduleId);

        if ($index === null) {
            wp_send_json_error('Schedule not found.');
            return;
        }

        $target = $schedules[$index];

        if (!$this->scheduleRecurrenceIsRegistered($target)) {
            wp_send_json_error(['message' => __('This backup plan uses a schedule frequency this version of WP STAGING cannot run.', 'wp-staging')]);
            return;
        }

        if (!empty($target['isPaused'])) {
            wp_send_json_error('Cannot run a paused schedule.');
            return;
        }

        if ($this->scheduleHasQueuedOrRunningBackup($scheduleId)) {
            wp_send_json_error(['message' => __('A backup for this schedule is already running. Wait for it to finish before starting another one.', 'wp-staging')]);
            return;
        }

        do_action(Cron::ACTION_CREATE_CRON_BACKUP, $target);
        wp_send_json_success();
    }







    private function scheduleHasQueuedOrRunningBackup(string $scheduleId): bool
    {
 
        $queue = WPStaging::make(Queue::class);
        $queue->markDanglingAs(Queue::STATUS_CANCELED, $queue->getStalledBreakpointDate(), Queue::SET_UPDATED_AT_TO_NOW);

        return $queue->countActionsByScheduleId($scheduleId, [Queue::STATUS_READY, Queue::STATUS_PROCESSING]) > 0;
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
        $schedules = $this->getSchedulesRunByCurrentSite();
        $this->reportThePlansThisSiteDoesNotRun();
        static::removeBackupSchedulesFromCron();

        $errors = [];

        foreach ($schedules as $schedule) {
            if (!empty($schedule['isPaused'])) {
                continue;
            }

            if (empty($schedule['scheduleId'])) {
                debug_log('[Schedule Backup Cron] Skipped a stored schedule without an id while re-creating the cron events.');
                continue;
            }

            if (!$this->scheduleRecurrenceIsRegistered($schedule)) {
                debug_log(sprintf(
                    '[Schedule Backup Cron] Skipped schedule %s while re-creating the cron events: this version of WP STAGING does not register the recurrence %s.',
                    $schedule['scheduleId'],
                    $schedule['schedule'] ?? ''
                ), 'info', false);
                continue;
            }

            $timeToSchedule = new \DateTime('now', wp_timezone());




            if (isset(wp_get_schedules()[$schedule['schedule']]) && isset($schedule['firstSchedule']) && ($schedule['scheduleId'] !== $scheduleBeingEdit)) {
                $this->setNextSchedulingDate($timeToSchedule, $schedule);
            } else {
                $dayOfWeek = Cron::extractDayFromSchedule($schedule['schedule']);
                $this->setUpcomingDateTime($timeToSchedule, $this->scheduleTimeOfDay($schedule), $dayOfWeek, $schedule['schedule']);
            }

 
            $result = wp_schedule_event($timeToSchedule->format('U'), $schedule['schedule'], Cron::ACTION_CREATE_CRON_BACKUP, [self::cronEventArguments($schedule['scheduleId'])]);

 
 
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




    private function reportThePlansThisSiteDoesNotRun()
    {
        if (!is_multisite()) {
            return;
        }

        $scheduleIds = [];
        foreach ($this->getSchedules() as $schedule) {
            if ($this->scheduleIsRunByCurrentSite($schedule)) {
                continue;
            }

            $scheduleIds[] = $this->readableScheduleId($schedule);
        }

        if (empty($scheduleIds)) {
            return;
        }

        debug_log(sprintf(
            '[Schedule Backup Cron] Skipped these schedules while re-creating the cron events on site %d, because the plan names another site of the network or names none: %s.',
            get_current_blog_id(),
            implode(', ', $scheduleIds)
        ), 'info', false);
    }





    private function readableScheduleId(array $schedule): string
    {
        $scheduleId = isset($schedule['scheduleId']) && is_scalar($schedule['scheduleId']) ? (string)$schedule['scheduleId'] : '';

        return $scheduleId === '' ? '(unknown)' : $scheduleId;
    }









    public function reCreateCronIfSchedulesExist(): bool
    {
        if (empty($this->getSchedulesRunByCurrentSite())) {
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

        $activeSchedules = array_filter($this->getSchedulesRunByCurrentSite(), function (array $schedule): bool {
            return empty($schedule['isPaused']) && $this->scheduleRecurrenceIsRegistered($schedule);
        });
        if (empty($activeSchedules)) {
            return true;
        }

        $this->detectScheduledBackupWarning($activeSchedules);

        return $this->cronWarningType === '';
    }








    private function detectScheduledBackupWarning(array $activeSchedules)
    {
        $lastFailure = get_option(self::OPTION_LAST_BACKUP_FAILURE);
        if (is_array($lastFailure) && !empty($lastFailure['time']) && (int)$lastFailure['time'] > $this->getLastScheduledBackupSuccessTime()) {
            $this->cronWarningType          = self::CRON_WARNING_TYPE_FAILURE;
            $this->lastBackupFailureMessage = $lastFailure['message'] ?? '';
            return;
        }

        foreach ($activeSchedules as $schedule) {
            if ($this->scheduleMissedARun($schedule)) {
                $this->cronWarningType = self::CRON_WARNING_TYPE_OVERDUE;
                return;
            }
        }
    }









    private function scheduleMissedARun(array $schedule): bool
    {
        $runsRecordedSince = $this->runsRecordedSince($schedule);
        if ($runsRecordedSince === 0) {
            return false;
        }

        $dueOccurrence = $this->lastOccurrenceDueAtOrBefore($schedule, time() - self::OVERDUE_GRACE_PERIOD);
        if ($dueOccurrence === null || $dueOccurrence <= $runsRecordedSince) {
            return false;
        }

        return !$this->scheduleHasQueuedOrRunningBackup((string)($schedule['scheduleId'] ?? ''));
    }









    private function runsRecordedSince(array $schedule): int
    {
        return max(
            (int)($schedule['lastRunTime'] ?? 0),
            (int)($schedule[self::FIELD_RUNS_RECORDED_SINCE] ?? 0)
        );
    }








    private function lastOccurrenceDueAtOrBefore(array $schedule, int $deadline)
    {
        $firstOccurrence = $this->firstOccurrenceTimestamp($schedule);
        $interval        = $this->intervalInSeconds($schedule);

        if ($firstOccurrence === null || $interval <= 0 || $firstOccurrence > $deadline) {
            return null;
        }

        return $firstOccurrence + ((int)floor(($deadline - $firstOccurrence) / $interval) * $interval);
    }








    private function firstOccurrenceTimestamp(array $schedule)
    {
        if (!empty($schedule['firstSchedule'])) {
            return (int)$schedule['firstSchedule'];
        }

        if (!empty($schedule['lastRunTime'])) {
            return (int)$schedule['lastRunTime'];
        }

        return null;
    }





    private function intervalInSeconds(array $schedule): int
    {
        $recurrence  = (string)($schedule['schedule'] ?? '');
        $wpSchedules = wp_get_schedules();

        return isset($wpSchedules[$recurrence]) ? (int)$wpSchedules[$recurrence]['interval'] : 0;
    }








    private function scheduleRecurrenceIsRegistered(array $schedule): bool
    {
        return Cron::isRecurrenceRegistered((string)($schedule['schedule'] ?? ''));
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

 
    public function isWpCronDisabled(): bool
    {
        return defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
    }




    public function getWarningType(): string
    {
        return $this->cronWarningType;
    }




    public function getLastBackupFailureMessage(): string
    {
        return $this->lastBackupFailureMessage;
    }







    public function shouldShowMenuBadge(): bool
    {
        return !$this->checkCronStatus();
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









    public function getUpcomingScheduleTime(string $time, string $scheduleRecurrence): int
    {
        $dayOfWeek = Cron::extractDayFromSchedule($scheduleRecurrence);
        $datetime  = new \DateTime('now', wp_timezone());
        $this->setUpcomingDateTime($datetime, $time, $dayOfWeek, $scheduleRecurrence);

        return $datetime->getTimestamp();
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
            $wpSchedules = wp_get_schedules();
            if (
                $scheduleRecurrence !== null &&
                isset($wpSchedules[$scheduleRecurrence]) &&
                (int)$wpSchedules[$scheduleRecurrence]['interval'] < DAY_IN_SECONDS
            ) {
 
 
                $datetime->add(new \DateInterval('PT' . (int)$wpSchedules[$scheduleRecurrence]['interval'] . 'S'));
                return;
            }

 
            if ((int)sprintf('%02d%02d', $hourAndMinute[0], $hourAndMinute[1]) <= (int)$datetime->format('Hi')) {
                $datetime->add(new \DateInterval('P1D'));
            }
        }

        $datetime->setTime($hourAndMinute[0], $hourAndMinute[1]);
    }







    private function hasNoRecomputableAnchor(int $interval): bool
    {
        return $interval >= MONTH_IN_SECONDS || ($interval > 0 && $interval < DAY_IN_SECONDS);
    }








    protected function setNextSchedulingDate(DateTime &$datetime, array $schedule)
    {
        $next = $schedule['firstSchedule'];
        $now  = $datetime->getTimestamp();
        if ($next >= $now) {
            if ($this->hasNoRecomputableAnchor($this->intervalInSeconds($schedule))) {
                $datetime->setTimestamp($next);
                return;
            }

            $dayOfWeek = Cron::extractDayFromSchedule($schedule['schedule']);
            $this->setUpcomingDateTime($datetime, $this->scheduleTimeOfDay($schedule), $dayOfWeek, $schedule['schedule']);
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

        if ($this->isReportThrottled($reportType)) {
            return false;
        }

        if (empty($message)) {
            return false;
        }

        if (empty($title)) {
            $title = $this->getDefaultReportTitle($reportType);
        }

        $this->throttleReport($reportType);

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








    private function isReportThrottled(string $reportType): bool
    {
        return $reportType === self::REPORT_TYPE_ERROR && get_transient(self::TRANSIENT_BACKUP_SCHEDULE_ERROR_REPORT_SENT) !== false;
    }





    private function throttleReport(string $reportType)
    {
        if ($reportType !== self::REPORT_TYPE_ERROR) {
            return;
        }

        set_transient(self::TRANSIENT_BACKUP_SCHEDULE_ERROR_REPORT_SENT, true, 5 * 60);
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
}
