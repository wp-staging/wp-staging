<?php

namespace WPStaging\Backup;

use DateTime;
use WPStaging\Backup\BackgroundProcessing\Backup\PrepareBackup;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Core\Cron\Cron;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\FeatureDetection;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\Nonce;
use WPStaging\Framework\Utils\ServerVars;
use WPStaging\Notifications\Notifications;

use function WPStaging\functions\debug_log;

/**
 * BackupScheduler - Manages backup scheduling and cron jobs
 *
 * Day-Specific Weekly Schedules:
 * - Weekly schedules support day-specific variants (e.g., wpstg_weekly_1 for Monday, wpstg_weekly_7 for Sunday)
 * - Day numbering uses ISO 8601 standard: 1=Monday, 2=Tuesday, ..., 7=Sunday
 * - This makes it easy to extend to other schedules in the future
 *
 * Backward Compatibility:
 * - Existing plain 'wpstg_weekly' schedules (without day suffix) continue to work
 * - They run every 7 days from their original start time, regardless of day
 * - The system automatically handles both old and new schedule formats
 */
class BackupScheduler
{
    /** @var string */
    const OPTION_BACKUP_SCHEDULE_ERROR_REPORT = 'wpstg_backup_schedules_send_error_report';

    /** @var string */
    const OPTION_BACKUP_SCHEDULE_WARNING_REPORT = 'wpstg_backup_schedules_send_warning_report';

    /** @var string */
    const OPTION_BACKUP_SCHEDULE_GENERAL_REPORT = 'wpstg_backup_schedules_send_general_report';

    /** @var string */
    const OPTION_BACKUP_SCHEDULE_SLACK_ERROR_REPORT = 'wpstg_backup_schedules_send_slack_error_report';

    /** @var string */
    const OPTION_BACKUP_SCHEDULE_REPORT_SLACK_WEBHOOK = 'wpstg_backup_schedules_report_slack_webhook';

    /** @var string */
    const OPTION_BACKUP_SCHEDULES = 'wpstg_backup_schedules';

    /** @var string */
    const TRANSIENT_BACKUP_SCHEDULE_ERROR_REPORT_SENT = 'wpstg.backup.schedules.error_report_sent';

    /** @var string */
    const TRANSIENT_BACKUP_SCHEDULE_WARNING_REPORT_SENT = 'wpstg.backup.schedules.warning_report_sent';

    /** @var string */
    const TRANSIENT_BACKUP_SCHEDULE_GENERAL_REPORT_SENT = 'wpstg.backup.schedules.general_report_sent';

    /** @var string */
    const TRANSIENT_BACKUP_SCHEDULE_SLACK_REPORT_SENT = 'wpstg.backup.schedules.slack_report_sent';

    /** @var string */
    const REPORT_TYPE_ERROR = 'error';

    /** @var string */
    const REPORT_TYPE_WARNING = 'warning';

    /** @var string */
    const REPORT_TYPE_GENERAL = 'general';

    /** @var string */
    const FILTER_SCHEDULES_BACKUP_INTERVAL = 'wpstg.schedulesBackup.interval';

    /** @var string */
    const FILTER_CRON_REQUEST = 'cron_request';

    /** @var BackupsFinder */
    protected $backupsFinder;

    /** @var ProcessLock */
    protected $processLock;

    /** @var BackupDeleter */
    protected $backupDeleter;

    /**
     * @var Notifications
     */
    protected $notifications;

    /**
     * Store cron related message
     * @var string
     */
    protected $cronMessage;

    /** @var int */
    protected $numberOverdueCronjobs = 0;

    /**
     * @param BackupsFinder $backupsFinder
     * @param ProcessLock $processLock
     * @param BackupDeleter $backupDeleter
     * @param Notifications $notifications
     */
    public function __construct(BackupsFinder $backupsFinder, ProcessLock $processLock, BackupDeleter $backupDeleter, Notifications $notifications)
    {
        $this->backupsFinder = $backupsFinder;
        $this->processLock   = $processLock;
        $this->backupDeleter = $backupDeleter;
        $this->notifications = $notifications;

        $this->countOverdueCronjobs();
    }

    /**
     * @return array
     */
    public function getSchedules(): array
    {
        $schedules = get_option(static::OPTION_BACKUP_SCHEDULES, []);
        if (is_array($schedules)) {
            return $schedules;
        }

        return [];
    }

    /**
     * @param JobBackupDataDto $jobBackupDataDto
     * @return void
     */
    public function maybeDeleteOldBackups(JobBackupDataDto $jobBackupDataDto)
    {
        $scheduleId = $jobBackupDataDto->getScheduleId();

        // Not a scheduled backup, nothing to do.
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

        // Early bail: Not enough backups to trigger the rotation
        if (count($backupFiles) < $maxAllowedBackupFiles) {
            return;
        }

        // Sort backups, older first
        uasort($backupFiles, function ($backup1, $backup2) {
            /**
             * @var \SplFileInfo $backup1
             * @var \SplFileInfo $backup2
             */
            if ($backup1->getMTime() === $backup2->getMTime()) {
                return 0;
            }

            return $backup1->getMTime() < $backup2->getMTime() ? -1 : 1;
        });

        // Make sure array indexes are correctly ordered.
        $backupFiles = array_values($backupFiles);

        // Get exceeding backups, including an extra one for the backup that will be created right now.
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

    /**
     * @param JobBackupDataDto $jobBackupDataDto
     * @param string $scheduleId
     * @return void
     * @throws \Exception
     */
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
            'subsiteBlogId'                  => $jobBackupDataDto->getSubsiteBlogId(), // required for network subsite backup type
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
            'isWpCliRequest'                 => true, // should be true otherwise multisite backup will not work
            'backupExcludedDirectories'      => $jobBackupDataDto->getBackupExcludedDirectories(),
        ];

        if (wp_next_scheduled(Cron::ACTION_CREATE_CRON_BACKUP, [$backupSchedule])) {
            debug_log('[Schedule Backup Cron] Early bailed when registering the cron to create a backup on a schedule, because it already exists');

            return;
        }

        $this->registerScheduleInDb($backupSchedule);
        $this->reCreateCron();
    }

    /**
     * Registers a schedule in the Db.
     * @param array $backupSchedule
     * @return bool false on error or if nothing would be updated
     */
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

    /**
     * AJAX callback that processes the backup schedule.
     *
     * @param array $backupData
     * @return void
     */
    public function createCronBackup(array $backupData)
    {
        // Cron is hell to debug, so let's log everything that happens.
        $logId = wp_generate_password(4, false);

        debug_log(sprintf("[Schedule Backup Cron - %s] Received request to create a backup using Cron. Backup Data: %s", $logId, wp_json_encode($backupData)), 'info', false);

        try {
            debug_log(sprintf("[Schedule Backup Cron - %s] Preparing job", $logId), 'info', false);
            $jobId = WPStaging::make(PrepareBackup::class)->prepare($backupData);
            if ($jobId instanceof \WP_Error) {
                debug_log(sprintf("[Schedule Backup Cron - %s] Failed to create backup: %s", $logId, $jobId->get_error_message()));
                return;
            }

            debug_log(sprintf("[Schedule Backup Cron - %s] Successfully received a Job ID: %s", $logId, $jobId), 'info', false);
        } catch (\Exception $e) {
            debug_log("[Schedule Backup Cron - $logId] Exception thrown while preparing the Backup: " . $e->getMessage());
        }
    }

    /**
     * Ajax callback to dismiss a schedule.
     * @return void
     */
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

    /**
     * Deletes a backup schedule.
     *
     * @param string $scheduleId The schedule ID to delete.
     * @return void
     */
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

        if ($reCreateCron === false) {
            return;
        }

        $this->reCreateCron();
    }

    /**
     * @param string|null $scheduleBeingEdit The schedule ID being edited. If this is set, it will be ignored when re-creating the Cron events.
     * @return bool
     * @throws \Exception
     * @see OPTION_BACKUP_SCHEDULES The Db option that is the source of truth for Cron events.
     *                              The backup schedule cron events are deleted and re-created
     *                              based on what is in this db option.
     *
     *                              This way, we only care about preserving this option on Backup
     *                              Restore or Push, and we don't have to worry about re-scheduling
     *                              the Cron events or removing leftover schedules.
     *
     */
    public function reCreateCron($scheduleBeingEdit = null): bool
    {
        $schedules = $this->getSchedules();
        static::removeBackupSchedulesFromCron();

        $errors = [];

        foreach ($schedules as $schedule) {
            $timeToSchedule = new \DateTime('now', wp_timezone());

            /**
             * New mechanism for recroning old jobs
             */
            if (isset(wp_get_schedules()[$schedule['schedule']]) && isset($schedule['firstSchedule']) && ($schedule['scheduleId'] !== $scheduleBeingEdit)) {
                $this->setNextSchedulingDate($timeToSchedule, $schedule);
            } else {
                $dayOfWeek = Cron::extractDayFromSchedule($schedule['schedule']);
                $this->setUpcomingDateTime($timeToSchedule, $schedule['time'], $dayOfWeek, $schedule['schedule']);
            }

            /** @see BackupServiceProvider::enqueueAjaxListeners */
            $result = wp_schedule_event($timeToSchedule->format('U'), $schedule['schedule'], Cron::ACTION_CREATE_CRON_BACKUP, [$schedule]);

            // Could not register Cron event.
            // Log errors but keep trying for the other cron events or all of them will be lost
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

    /**
     * Removes all backup schedule events from WordPress Cron array.
     *
     * This is static so that it can be called from WP STAGING deactivation hook
     * without having to bootstrap the entire plugin.
     *
     * This is a low-level function that can run when WP STAGING has not been
     * bootstrapped, so there's no autoload nor Container available.
     */
    public static function removeBackupSchedulesFromCron(): bool
    {
        $cron = get_option('cron');

        // Bail: Unexpected value - should never happen.
        if (!is_array($cron)) {
            return false;
        }

        // Remove any backup schedules from Cron
        foreach ($cron as $timestamp => &$events) {
            if (is_array($events)) {
                foreach ($events as $callback => &$args) {
                    if ($callback === Cron::ACTION_CREATE_CRON_BACKUP) {
                        unset($cron[$timestamp][$callback]);
                    }
                }
            }
        }

        // After removing the backup schedule events,
        // we might have timestamps with no events.
        // So we remove any leftover timestamps that don't have any events.
        $cron = array_filter($cron, function ($timestamps) {
            return !empty($timestamps);
        });

        update_option('cron', $cron);

        return true;
    }

    /**
     * Check cron status whether it is working or not
     * Logic is adopted from WP Crontrol plugin
     *
     * @return bool
     */
    public function checkCronStatus(): bool
    {
        global $wp_version;

        $this->cronMessage = '';
        // Add arrays to collect warnings and general messages
        $warningMessages = [];
        $generalMessages = [];
        // Track the overall result
        $cronStatusResult = true;

        if ($this->isCronjobsOverdue()) {
            if (WPStaging::isPro()) {
                $overdueMessage = sprintf(
                    __('There are %s scheduled WordPress tasks overdue. This means the WordPress cron jobs are not working properly, unless this a development site or no users are visiting this website. <a href="%s">Read this article</a> to find a solution.<br><br>', 'wp-staging'),
                    $this->numberOverdueCronjobs,
                    'https://wp-staging.com/docs/wp-cron-is-not-working-correctly/'
                );
                $this->cronMessage .= $overdueMessage;
                $warningMessages[] = $overdueMessage;

                if (WPStaging::make(ServerVars::class)->isLitespeed()) {
                    $litespeedMessage = sprintf(
                        Escape::escapeHtml(__('This site is using LiteSpeed server, this could prevent the scheduled backups from working properly. Please read <a href="%s" target="_blank">this article here</a> if the backup scheduling is not working properly.', 'wp-staging')),
                        'https://wp-staging.com/docs/scheduled-backups-do-not-work-hosting-company-uses-the-litespeed-webserver-fix-wp-cron/'
                    );
                    $this->cronMessage .= $litespeedMessage;
                    $generalMessages[] = $litespeedMessage;
                }
            } else {
                $overdueMessage = sprintf(
                    __('There are %s scheduled WordPress tasks overdue. This means the WordPress cron jobs are not working properly, unless this a development site or no users are visiting this website.<br> <a href="%s">Write to us in the forum</a> to get a solution for this issue from the WP STAGING support team.<br><br>', 'wp-staging'),
                    $this->numberOverdueCronjobs,
                    'https://wordpress.org/support/plugin/wp-staging/'
                );
                $this->cronMessage .= $overdueMessage;
                $warningMessages[] = $overdueMessage;

                if (WPStaging::make(ServerVars::class)->isLitespeed()) {
                    $litespeedMessage = sprintf(
                        Escape::escapeHtml(__('This site is using LiteSpeed server, this could prevent the scheduled backups from working properly. <a href="%s">Write to us in the forum</a> to get a solution for that issue.', 'wp-staging')),
                        'https://wordpress.org/support/plugin/wp-staging/'
                    );
                    $this->cronMessage .= $litespeedMessage;
                    $generalMessages[] = $litespeedMessage;
                }
            }
        }

        // Third party plugins that handle crons
        $thirdPartyCronPlugins = [
            '\HM\Cavalcade\Plugin\Job'         => 'Cavalcade',
            '\Automattic\WP\Cron_Control\Main' => 'Cron Control',
            '\KMM\KRoN\Core'                   => 'KMM KRoN',
        ];

        foreach ($thirdPartyCronPlugins as $class => $plugin) {
            if (class_exists($class)) {
                $thirdPartyMessage = sprintf(
                    __('WP Cron is being managed by a third party plugin: %s plugin.', 'wp-staging'),
                    $plugin
                );
                $this->cronMessage .= $thirdPartyMessage;
                $generalMessages[] = $thirdPartyMessage;

                $cronStatusResult = true;
                break;
            }
        }

        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            if (WPStaging::isPro()) {
                $disabledCronMessage = sprintf(
                    __('The background backup creation depends on WP-Cron but %s is set to %s in wp-config.php. Background processing might not work. Remove this constant or set its value to %s. Ignore this if you use an external cron job.', 'wp-staging'),
                    '<code>DISABLE_WP_CRON</code>',
                    '<code>true</code>',
                    '<code>false</code>'
                );
            } else {
                $disabledCronMessage = sprintf(
                    __('The background backup creation depends on WP-Cron but %s is set to %s in wp-config.php. Background processing might not work. Remove this constant or set its value to %s. Ignore this if you use an external cron job. <a href="%s" target="_blank">Ask us in the forum</a> if you need more information.', 'wp-staging'),
                    '<code>DISABLE_WP_CRON</code>',
                    '<code>true</code>',
                    '<code>false</code>',
                    'https://wordpress.org/support/plugin/wp-staging/'
                );
            }

            $this->cronMessage .= $disabledCronMessage;
            $warningMessages[] = $disabledCronMessage;

            $cronStatusResult = true;
        }

        if (defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON) {
            $alternateCronMessage = sprintf(
                __('The constant %s is set to true.', 'wp-staging'),
                'ALTERNATE_WP_CRON'
            );
            $this->cronMessage .= $alternateCronMessage;
            $generalMessages[] = $alternateCronMessage;

            $cronStatusResult = true;
        }

        // Don't do the next time expensive checking if no schedules are set
        if ($this->isSchedulesEmpty()) {
            return true;
        }

        $sslverify   = version_compare($wp_version, '4.0', '<');
        $doingWpCron = sprintf('%.22F', microtime(true));
        $urlEndpoint = add_query_arg('doing_wp_cron', $doingWpCron, site_url('wp-cron.php'));

        $cronRequest = apply_filters(self::FILTER_CRON_REQUEST, [
            'url'  => $urlEndpoint,
            'key'  => $doingWpCron,
            'args' => [
                'timeout'   => 10,
                'blocking'  => true,
                'sslverify' => apply_filters(FeatureDetection::FILTER_HTTPS_LOCAL_SSL_VERIFY, $sslverify),
            ],
        ]);

        $cronRequest['args']['blocking'] = true;
        $result = wp_remote_post($cronRequest['url'], $cronRequest['args']);

        // Action hook for internal use only: used during cron failure test
        Hooks::doAction('wpstg.tests.backup.scheduler.failing_schedule_error');

        if (is_wp_error($result)) {
            $errorCronMessage = "Can not create scheduled backups because cron jobs do not work on this site. Error: " . $result->get_error_message() . ". Can not reach endpoint: " . esc_url($urlEndpoint);
            // Only send the error report mail if error is caused by WP STAGING
            if ($this->isWpstgError()) {
                $this->sendErrorReport($errorCronMessage);
            }

            $this->cronMessage .= $errorCronMessage;

            $cronStatusResult = false;
        }

        if (wp_remote_retrieve_response_code($result) >= 300) {
            $httpWarningMessage = sprintf(
                __('Unexpected HTTP response code: %s. Cron jobs and backup schedule might still work, but we recommend checking the HTTP response of %s', 'wp-staging'),
                intval(wp_remote_retrieve_response_code($result)),
                esc_url($urlEndpoint)
            );
            $this->cronMessage .= $httpWarningMessage;
            $warningMessages[] = $httpWarningMessage;

            $cronStatusResult = false;
        }

        // Send accumulated warning and general reports ONLY ONCE at the end
        if (!empty($warningMessages)) {
            $this->sendWarningReport(implode("\n\n", $warningMessages));
        }

        if (!empty($generalMessages)) {
            $this->sendGeneralReport(implode("\n\n", $generalMessages));
        }

        return $cronStatusResult;
    }

    /**
     * @return bool
     */
    private function isCronjobsOverdue(): bool
    {
        return $this->numberOverdueCronjobs > 4;
    }

    /** @return string */
    public function getCronMessage(): string
    {
        return $this->cronMessage;
    }

    /** @return int */
    public function getOverdueCronJobsCount(): int
    {
        return $this->numberOverdueCronjobs;
    }

    /** @return bool */
    public function isWpCronDisabled(): bool
    {
        return defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
    }

    /** @return bool */
    public function hasOverdueCronJobs(): bool
    {
        return $this->isCronjobsOverdue();
    }

    /**
     * @return array An array where the first item is the timestamp, and the second is the backup callback.
     * @throws \Exception When there is no backup scheduled or one could not be found.
     */
    public function getNextBackupSchedule(): array
    {
        $cron = get_option('cron');

        // Bail: Unexpected value - should never happen.
        if (!is_array($cron)) {
            throw new \UnexpectedValueException();
        }

        ksort($cron, SORT_NUMERIC);

        // Remove any backup schedules from Cron
        foreach ($cron as $timestamp => &$events) {
            if (is_array($events)) {
                foreach ($events as $callback => &$args) {
                    if ($callback === Cron::ACTION_CREATE_CRON_BACKUP) {
                        return [$timestamp, $cron[$timestamp][$callback]];
                    }
                }
            }
        }

        // No results found
        throw new \OutOfBoundsException();
    }

    /**
     * Set date today or tomorrow for given DateTime object according to time
     *
     * @param DateTime $datetime
     * @param string|array $time
     * @param int|null $dayOfWeek Day of week (1-7, Monday-Sunday, ISO 8601) for weekly schedules
     * @param string|null $scheduleRecurrence The schedule recurrence type
     * @return void
     */
    protected function setUpcomingDateTime(DateTime &$datetime, $time, $dayOfWeek = null, $scheduleRecurrence = null)
    {
        if (is_array($time)) {
            $hourAndMinute = $time;
        } else {
            $hourAndMinute = explode(':', $time);
        }

        // For weekly schedules with a specific day of week
        $isWeeklySchedule = $scheduleRecurrence === Cron::WEEKLY ||
                           $scheduleRecurrence === Cron::EVERY_TWO_WEEKS ||
                           strpos($scheduleRecurrence, Cron::WEEKLY . '_') === 0;

        if ($dayOfWeek !== null && $isWeeklySchedule) {
            // Use ISO 8601 day numbers: 1 (Monday) through 7 (Sunday)
            // PHP's date('N') returns the same format
            $currentDayOfWeek = (int)$datetime->format('N');
            $targetDayOfWeek  = (int)$dayOfWeek;

            // Convert time to comparable integer HHMM
            $targetTimeInt    = (int) sprintf('%02d%02d', $hourAndMinute[0], $hourAndMinute[1]);
            $currentTimeInt   = (int) $datetime->format('Hi');
            $daysUntilTarget  = $targetDayOfWeek - $currentDayOfWeek;

            if ($daysUntilTarget < 0) {
                $daysUntilTarget += 7;
            }

            // If same day and target time already passed then schedule next week
            if ($daysUntilTarget === 0 && $targetTimeInt <= $currentTimeInt) {
                $daysUntilTarget = 7;
            }

            // Apply date shift
            if ($daysUntilTarget > 0) {
                $datetime->add(new \DateInterval("P{$daysUntilTarget}D"));
            }
        } else {
            // The event should be scheduled later today or tomorrow? Compares "Hi (Hourminute)" timestamps to figure out.
            if ((int)sprintf('%s%s', $hourAndMinute[0], $hourAndMinute[1]) < (int)$datetime->format('Hi')) {
                $datetime->add(new \DateInterval('P1D'));
            }
        }

        $datetime->setTime($hourAndMinute[0], $hourAndMinute[1]);
    }

    /**
     * Set the next scheduling date for the schedule
     *
     * @param DateTime $datetime
     * @param array $schedule
     * @return void
     */
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

    /**
     * Detect whether the last error is caused by WP STAGING
     *
     * @return bool
     */
    protected function isWpstgError(): bool
    {
        $error = error_get_last();
        if (!is_array($error)) {
            return false;
        }

        return strpos($error['file'], WPSTG_PLUGIN_SLUG) !== false;
    }

    /**
     * Send an error report email
     * A Generic title will be used if no title is provided
     * Internally uses sendEmailReport()
     *
     * @param string $message
     * @param string $title
     * @return bool
     */
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

    /**
     * Send a warning report email
     * A Generic title will be used if no title is provided
     * Internally uses sendEmailReport()
     *
     * @param string $message
     * @param string $title
     * @return bool
     */
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

    /**
     * Send a general report email
     * A Generic title will be used if no title is provided
     * Internally uses sendEmailReport()
     *
     * @param string $message
     * @param string $title
     * @return bool
     */
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

    /**
     * Send a report email
     * A Generic title will be used if no title is provided
     *
     * @param string $message
     * @param string $title
     * @return bool
     */
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

        // Only send the report mail once every 5 minutes
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

        // Set the transient to prevent sending the error report mail again for 5 minutes
        $transientName = $this->getReportTransientName($reportType);
        set_transient($transientName, true, 5 * 60);

        if (get_option(Notifications::OPTION_SEND_EMAIL_AS_HTML, false) === 'true') {
            return $this->notifications->sendEmailAsHTML($reportEmail, $title, $message);
        }

        return $this->notifications->sendEmail($reportEmail, $title, $message);
    }

    /**
     * Send a report slack
     * A Generic title will be used if no title is provided
     *
     * @param string $message
     * @param string $title
     * @return bool
     */
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

        // Only send the error report mail once every 5 minutes
        if (get_transient(self::TRANSIENT_BACKUP_SCHEDULE_SLACK_REPORT_SENT) !== false) {
            return false;
        }

        if (empty($message)) {
            return false;
        }

        if (empty($title)) {
            $title = esc_html__('WP Staging - Backup Report', 'wp-staging');
        }

        // Set the transient to prevent sending the error report mail again for 5 minutes
        set_transient(self::TRANSIENT_BACKUP_SCHEDULE_SLACK_REPORT_SENT, true, 5 * 60);
        return $this->notifications->sendSlack($webhook, $title, $message);
    }

    /**
     * Get the option name for a specific report type
     *
     * @param string $reportType
     * @return string
     */
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

    /**
     * Get the transient name for a specific report type
     *
     * @param string $reportType
     * @return string
     */
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

    /**
     * Get the default title for a specific report type
     *
     * @param string $reportType
     * @return string
     */
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

    /**
     * @return bool
     */
    private function isSchedulesEmpty(): bool
    {
        $schedules = get_option(static::OPTION_BACKUP_SCHEDULES, []);
        if (empty($schedules)) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    private function getCronJobs(): array
    {
        $cron = get_option('cron');
        if (!is_array($cron)) {
            return [];
        }

        return $cron;
    }

    /**
     * @return void
     */
    private function countOverdueCronjobs()
    {
        $cronJobs = $this->getCronJobs();
        $timeNow  = time();
        foreach ($cronJobs as $expectedExecutionTime => $cronJob) {
            if ($expectedExecutionTime < $timeNow) {
                $this->numberOverdueCronjobs++;
            }
        }
    }
}
