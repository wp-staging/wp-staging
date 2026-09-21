<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\BackupScheduler;
use WPStaging\Backup\Storage\Providers;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Core\Cron\Cron;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\Nonce;
use WPStaging\Framework\Utils\Times;

class ScheduleList
{
    private $backupScheduler;

    private $isPro;

 
    protected $providers;




    public function __construct(BackupScheduler $backupScheduler)
    {
        $this->backupScheduler = $backupScheduler;
        $this->providers       = WPStaging::make(Providers::class);
        $this->isPro           = WPStaging::isPro();
    }






    public function ajaxGetBackupScheduleSectionData()
    {
        if (!current_user_can((new Capabilities())->manageWPSTG())) {
            wp_send_json_error(null, 403);
            return;
        }

        if (!(new Nonce())->requestHasValidNonce(Nonce::WPSTG_NONCE)) {
            wp_send_json_error(null, 403);
            return;
        }

        $schedules  = $this->backupScheduler->getSchedulesRunByCurrentSite();
        $nextRunMap = $this->backupScheduler->getNextRunTimestampsByScheduleId();

        $this->repairMissingCronEventsIfNeeded($schedules, $nextRunMap);

        WPStaging::silenceLogs();
        $lastBackup = get_option(FinishBackupTask::OPTION_LAST_BACKUP);
        WPStaging::silenceLogs(false);

        $lastBackupTimestamp = null;
        if (is_array($lastBackup) && !empty($lastBackup['endTime'])) {
            $lastBackupTimestamp = (int)$lastBackup['endTime'];
        }

        $schedulesData = [];
        foreach ($schedules as $schedule) {
            $storages     = isset($schedule['storages']) && is_array($schedule['storages']) ? $schedule['storages'] : [];
            $storageKeys  = $this->keysWithAResolvableStorageName($storages);
            $storageNames = array_map([$this, 'storageDisplayName'], $storageKeys);

            $reconnectStorageKeys  = $this->resolveReconnectStorageKeys($schedule);
            $reconnectStorageNames = array_map([$this, 'storageDisplayName'], $reconnectStorageKeys);

            $schedulesData[] = [
                'scheduleId'                     => $schedule['scheduleId'] ?? '',
                'name'                           => $schedule['name'] ?? '',
                'lastRunStatus'                  => $schedule['lastRunStatus'] ?? null,
                'lastRunTimestamp'               => $schedule['lastRunTime'] ?? null,
                'lastRunError'                   => esc_html($schedule['lastRunError'] ?? ''),
                'lastRunJobId'                   => $schedule['lastRunJobId'] ?? '',
                'recurrence'                     => Cron::getCronDisplayName($schedule['schedule'] ?? ''),
                'storageNames'                   => $storageNames,
                'storageKeys'                    => $storageKeys,
                'nextRunTimestamp'               => $this->normalizeNextRunTimestamp($nextRunMap[$schedule['scheduleId'] ?? ''] ?? null, $schedule['schedule'] ?? ''),
                'isPro'                          => $this->isPro,
                'isRunnableOnThisVersion'        => Cron::isRecurrenceRegistered($schedule['schedule'] ?? ''),
                'runsPredateTheRecord'           => $this->runsPredateTheRecord($schedule),
                'isExportingDatabase'            => !empty($schedule['isExportingDatabase']),
                'isExportingPlugins'             => !empty($schedule['isExportingPlugins']),
                'isExportingMuPlugins'           => !empty($schedule['isExportingMuPlugins']),
                'isExportingThemes'              => !empty($schedule['isExportingThemes']),
                'isExportingUploads'             => !empty($schedule['isExportingUploads']),
                'isExportingOtherWpContentFiles' => !empty($schedule['isExportingOtherWpContentFiles']),
                'isExportingOtherWpRootFiles'    => !empty($schedule['isExportingOtherWpRootFiles']),
                'isPaused'                       => !empty($schedule['isPaused']),
                'reconnectStorageKeys'           => $reconnectStorageKeys,
                'reconnectStorageNames'          => $reconnectStorageNames,
            ];
        }

        $schedulesData = array_reverse($schedulesData);
        $stats         = $this->computeScheduleStats($schedulesData);

        wp_send_json_success([
            'stats'               => [
                'total'          => count($schedulesData),
                'failed'         => $stats['failed'],
                'needsAttention' => $stats['needsAttention'],
            ],
            'lastBackupTimestamp' => $lastBackupTimestamp,
            'schedules'           => $schedulesData,
            'currentTimestamp'    => time(),
            'currentTime'         => WPStaging::make(Times::class)->getCurrentTime(),
        ]);
    }






    public function ajaxGetReconnectUrl()
    {
        if (!current_user_can((new Capabilities())->manageWPSTG())) {
            wp_send_json_error(null, 403);
            return;
        }

        if (!(new Nonce())->requestHasValidNonce(Nonce::WPSTG_NONCE)) {
            wp_send_json_error(null, 403);
            return;
        }

        $storageKey = Sanitize::sanitizeString($_POST['storageKey'] ?? '');
        if (empty($storageKey)) {
            wp_send_json_error('Missing storageKey.');
            return;
        }

        $authClass = $this->providers->getStorageProperty($storageKey, 'authClass');
        if ($authClass === false || !class_exists($authClass)) {
            wp_send_json_error('Unknown or unavailable storage provider.');
            return;
        }

        $authProvider = WPStaging::make($authClass);
        if (!method_exists($authProvider, 'getAuthenticationURL')) {
            wp_send_json_error('This storage provider does not support OAuth reconnect.');
            return;
        }

        $url = $authProvider->getAuthenticationURL('wpstg_backup');
        wp_send_json_success(['url' => $url]);
    }








    protected function computeScheduleStats(array $schedulesData): array
    {
        $success        = 0;
        $failed         = 0;
        $needsAttention = 0;

        foreach ($schedulesData as $entry) {
            $status = $this->resolveScheduleStatus($entry);

            if ($status === 'needs-attention') {
                $needsAttention++;
                continue;
            }

            if ($status === 'failed') {
                $failed++;
                continue;
            }

            if ($status === 'requires-pro') {
                continue;
            }

            if (!empty($entry['lastRunTimestamp']) && $entry['lastRunStatus'] === 'success') {
                $success++;
            }
        }

        return [
            'success'        => $success,
            'failed'         => $failed,
            'needsAttention' => $needsAttention,
        ];
    }








    protected function resolveScheduleStatus(array $entry): string
    {
        if (!empty($entry['isPaused'])) {
            return 'paused';
        }

        if (($entry['isRunnableOnThisVersion'] ?? true) === false && empty($entry['isPro'])) {
            return 'requires-pro';
        }

        if ($entry['lastRunStatus'] === 'failed') {
            return empty($entry['reconnectStorageKeys']) ? 'failed' : 'needs-attention';
        }

        return 'active';
    }









    protected function repairMissingCronEventsIfNeeded(array $schedules, array &$nextRunMap)
    {
        foreach ($schedules as $schedule) {
            if (!empty($schedule['isPaused'])) {
                continue;
            }

            $sid = $schedule['scheduleId'] ?? '';
            if ($sid === '' || isset($nextRunMap[$sid])) {
                continue;
            }

            if (!Cron::isRecurrenceRegistered($schedule['schedule'] ?? '')) {
                continue;
            }

            $this->backupScheduler->reCreateCron();
            $nextRunMap = $this->backupScheduler->getNextRunTimestampsByScheduleId();
            return;
        }
    }








    protected function runsPredateTheRecord(array $schedule): bool
    {
        if (!empty($schedule['lastRunTime'])) {
            return false;
        }

        $recordedSince = (int)($schedule[BackupScheduler::FIELD_RUNS_RECORDED_SINCE] ?? 0);

        return $recordedSince > 0 && (int)($schedule['firstSchedule'] ?? 0) < $recordedSince;
    }









    protected function normalizeNextRunTimestamp($timestamp, string $recurrence)
    {
        if ($timestamp === null) {
            return null;
        }

        $now = time();
        if ($timestamp >= $now) {
            return $timestamp;
        }

        $wpSchedules = wp_get_schedules();
        if (!isset($wpSchedules[$recurrence])) {
            return $timestamp;
        }

        $interval = (int)$wpSchedules[$recurrence]['interval'];
        if ($interval <= 0) {
            return $timestamp;
        }

        $missedIntervals = (int)ceil(($now - $timestamp) / $interval);
        $timestamp      += $interval * $missedIntervals;

        return $timestamp;
    }







    protected function resolveReconnectStorageKeys(array $schedule): array
    {
        return is_array($schedule['reconnectStorageKeys'] ?? null) ? $schedule['reconnectStorageKeys'] : [];
    }








    protected function keysWithAResolvableStorageName(array $storageKeys): array
    {
        return array_values(array_filter($storageKeys, function ($storageKey) {
            return $storageKey === 'localStorage' || !empty($this->providers->getStorageProperty($storageKey, 'name'));
        }));
    }








    protected function storageDisplayName(string $storageKey): string
    {
        if ($storageKey === 'localStorage') {
            return esc_html__('Local Storage', 'wp-staging');
        }

        $name = $this->providers->getStorageProperty($storageKey, 'name');

        return empty($name) ? $storageKey : $name;
    }
}
