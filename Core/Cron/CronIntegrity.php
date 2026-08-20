<?php

namespace WPStaging\Core\Cron;

use WPStaging\Backup\BackupScheduler;
use WPStaging\Framework\BackgroundProcessing\BackgroundProcessingServiceProvider;
use WPStaging\Framework\BackgroundProcessing\FeatureDetection;
use WPStaging\Framework\BackgroundProcessing\QueueProcessor;

use function WPStaging\functions\debug_log;
























class CronIntegrity
{
 
    const TRANSIENT_INTEGRITY_CHECKED = 'wpstg.cron.integrity_checked';

 
    const THROTTLE_SECONDS = HOUR_IN_SECONDS;

 
    private $backupScheduler;

 
    private $ranThisRequest = false;

    public function __construct(BackupScheduler $backupScheduler)
    {
        $this->backupScheduler = $backupScheduler;
    }




    public function checkAndRepair()
    {
        if ($this->ranThisRequest) {
            return;
        }

        $this->ranThisRequest = true;

 
 
 
 
        if (get_transient(self::TRANSIENT_INTEGRITY_CHECKED)) {
            return;
        }

        set_transient(self::TRANSIENT_INTEGRITY_CHECKED, 1, self::THROTTLE_SECONDS);

        try {
            debug_log('[Cron Integrity] Snapshot: ' . wp_json_encode($this->buildEnvironmentSnapshot()), 'debug', false);

            $this->checkStaticEvents();
            $this->checkBackupSchedules();
        } catch (\Throwable $t) {
            debug_log('[Cron Integrity] Uncaught failure during checkAndRepair: ' . $t->getMessage(), 'info', false);
        }
    }


















    private function checkStaticEvents()
    {
        $events = [
            [Cron::ACTION_DAILY_EVENT,                                   'daily'],
            [Cron::ACTION_WEEKLY_EVENT,                                  'weekly'],
            [BackgroundProcessingServiceProvider::ACTION_QUEUE_MAINTAIN, Cron::DAILY],
            [QueueProcessor::ACTION_QUEUE_PROCESS,                       Cron::HOURLY],
            [FeatureDetection::ACTION_AJAX_SUPPORT_FEATURE_DETECTION,    Cron::WEEKLY],
        ];

        $registeredRecurrences = wp_get_schedules();
        $snapshot              = [];

        foreach ($events as $event) {
            $action     = $event[0];
            $recurrence = $event[1];

            $existing     = wp_get_scheduled_event($action);
            $next         = wp_next_scheduled($action);
            $recurrenceOk = isset($registeredRecurrences[$recurrence]);
            $hasHandler   = has_action($action) !== false;

            $actualRecurrence     = ($existing && isset($existing->schedule)) ? $existing->schedule : null;
            $recurrenceMismatches = $existing && $actualRecurrence !== $recurrence;

            $snapshot[$action] = [
                'expectedRecurrence'   => $recurrence,
                'actualRecurrence'     => $actualRecurrence,
                'recurrenceRegistered' => $recurrenceOk,
                'hasHandler'           => $hasHandler,
                'nextScheduled'        => $next,
                'nextScheduledHuman'   => $next ? gmdate('Y-m-d H:i:s', $next) . ' UTC' : null,
                'recurrenceMismatches' => $recurrenceMismatches,
            ];

            if ($next !== false && !$recurrenceMismatches) {
                continue;
            }

            if (!$recurrenceOk) {
 
 
                continue;
            }

            if (!$hasHandler) {
 
 
 
                continue;
            }

            if ($recurrenceMismatches) {
                debug_log(sprintf(
                    '[Cron Integrity] Event "%s" has wrong recurrence (actual=%s, expected=%s), clearing before re-registering.',
                    $action,
                    $actualRecurrence,
                    $recurrence
                ), 'info', false);
                wp_clear_scheduled_hook($action);
            }

            $result = wp_schedule_event(time(), $recurrence, $action);
            $ok     = $result !== false && !($result instanceof \WP_Error);
            debug_log(sprintf(
                '[Cron Integrity] Re-registering static event "%s" (recurrence=%s): %s',
                $action,
                $recurrence,
                $ok ? 'ok' : 'FAILED (' . var_export($result, true) . ')'
            ), 'info', false);
        }

        debug_log('[Cron Integrity] Static events snapshot: ' . wp_json_encode($snapshot), 'debug', false);
    }
















    private function checkBackupSchedules()
    {
        $schedules        = $this->backupScheduler->getSchedules();
        $scheduledByCron  = $this->findScheduledBackupCrons();
        $orphanedCronIds  = $this->findOrphanedBackupCronScheduleIds($schedules, $scheduledByCron);
        $hasHandler       = has_action(Cron::ACTION_CREATE_CRON_BACKUP) !== false;

        if (empty($schedules) && empty($orphanedCronIds)) {
            debug_log('[Cron Integrity] No backup schedules configured and no orphaned backup crons.', 'debug', false);
            return;
        }

        $snapshot                      = $this->buildBackupSchedulesSnapshot($schedules, $scheduledByCron);
        $snapshot['orphanedCronIds']   = $orphanedCronIds;
        $snapshot['hasHandler']        = $hasHandler;
        debug_log('[Cron Integrity] Backup schedules snapshot: ' . wp_json_encode($snapshot), 'debug', false);

        $needsRepair = !empty($snapshot['missingScheduleIds'])
            || !empty($snapshot['wrongRecurrenceScheduleIds'])
            || !empty($orphanedCronIds);
        if (!$needsRepair) {
            return;
        }

        if (!$hasHandler) {
 
 
 
 
 
            debug_log('[Cron Integrity] Skipping backup cron repair: no handler attached for ' . Cron::ACTION_CREATE_CRON_BACKUP . ' on this request.', 'info', false);
            return;
        }

        debug_log(sprintf(
            '[Cron Integrity] Repairing backup cron events. Missing: [%s]. Wrong recurrence: [%s]. Orphaned: [%s]. Unrepairable (recurrence unregistered): [%s].',
            implode(', ', $snapshot['missingScheduleIds']),
            implode(', ', $snapshot['wrongRecurrenceScheduleIds']),
            implode(', ', $orphanedCronIds),
            implode(', ', $snapshot['unrepairableScheduleIds'])
        ), 'info', false);

        try {
            $result = $this->backupScheduler->reCreateCron();
            debug_log('[Cron Integrity] reCreateCron() returned: ' . var_export($result, true), 'info', false);
        } catch (\Throwable $e) {
            debug_log('[Cron Integrity] reCreateCron() threw: ' . $e->getMessage(), 'info', false);
        }
    }









    private function findOrphanedBackupCronScheduleIds(array $schedules, array $scheduledByCron): array
    {
        $knownIds = [];
        foreach ($schedules as $schedule) {
            if (isset($schedule['scheduleId'])) {
                $knownIds[$schedule['scheduleId']] = true;
            }
        }

        $orphans = [];
        foreach (array_keys($scheduledByCron) as $cronScheduleId) {
            if (!isset($knownIds[$cronScheduleId])) {
                $orphans[] = $cronScheduleId;
            }
        }

        return array_values(array_unique($orphans));
    }












    private function findScheduledBackupCrons(): array
    {
        $cron = get_option('cron');
        if (!is_array($cron)) {
            return [];
        }

        $scheduled = [];
        foreach ($cron as $timestamp => $events) {
            if (!is_array($events) || !isset($events[Cron::ACTION_CREATE_CRON_BACKUP])) {
                continue;
            }

            foreach ($events[Cron::ACTION_CREATE_CRON_BACKUP] as $entry) {
                if (!isset($entry['args'][0]['scheduleId'])) {
                    continue;
                }

                $scheduleId = $entry['args'][0]['scheduleId'];
 
 
                if (!isset($scheduled[$scheduleId])) {
                    $scheduled[$scheduleId] = [
                        'recurrence' => isset($entry['schedule']) ? $entry['schedule'] : null,
                        'timestamp'  => (int)$timestamp,
                    ];
                }
            }
        }

        return $scheduled;
    }




    private function buildEnvironmentSnapshot(): array
    {
        return [
            'serverTimeUtc'   => gmdate('Y-m-d H:i:s') . ' UTC',
            'siteTimezone'    => wp_timezone()->getName(),
            'disableWpCron'   => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
            'alternateWpCron' => defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON,
        ];
    }






    private function buildBackupSchedulesSnapshot(array $schedules, array $scheduledByCron): array
    {
        $registeredRecurrences = wp_get_schedules();
        $perSchedule           = [];
        $missing               = [];
        $wrongRecurrence       = [];
        $unrepairable          = [];

        foreach ($schedules as $schedule) {
            $recurrence       = isset($schedule['schedule']) ? $schedule['schedule'] : null;
            $scheduleId       = isset($schedule['scheduleId']) ? $schedule['scheduleId'] : '(unknown)';
            $scheduledEntry   = $scheduledByCron[$scheduleId] ?? null;
            $isScheduled      = $scheduledEntry !== null;
            $actualRecurrence = $scheduledEntry['recurrence'] ?? null;
            $next             = $scheduledEntry['timestamp'] ?? false;
            $recurrenceOk     = $recurrence !== null && isset($registeredRecurrences[$recurrence]);
            $wrongRec         = $isScheduled && $actualRecurrence !== $recurrence;

            if (!$isScheduled) {
 
 
 
                if ($recurrenceOk) {
                    $missing[] = $scheduleId;
                } else {
                    $unrepairable[] = $scheduleId;
                }
            } elseif ($wrongRec && $recurrenceOk) {
                $wrongRecurrence[] = $scheduleId;
            }

            $perSchedule[] = [
                'scheduleId'           => $scheduleId,
                'recurrence'           => $recurrence,
                'actualRecurrence'     => $actualRecurrence,
                'time'                 => isset($schedule['time']) ? $schedule['time'] : null,
                'recurrenceRegistered' => $recurrenceOk,
                'isScheduled'          => $isScheduled,
                'recurrenceMismatches' => $wrongRec,
                'nextScheduled'        => $next,
                'nextScheduledHuman'   => $next ? gmdate('Y-m-d H:i:s', $next) . ' UTC' : null,
                'secondsUntilNext'     => $next ? ($next - time()) : null,
            ];
        }

        return [
            'totalSchedules'             => count($schedules),
            'totalMissing'               => count($missing),
            'totalWrongRecurrence'       => count($wrongRecurrence),
            'totalUnrepairable'          => count($unrepairable),
            'missingScheduleIds'         => $missing,
            'wrongRecurrenceScheduleIds' => $wrongRecurrence,
            'unrepairableScheduleIds'    => $unrepairable,
            'schedules'                  => $perSchedule,
        ];
    }
}
