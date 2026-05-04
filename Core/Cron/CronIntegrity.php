<?php

namespace WPStaging\Core\Cron;

use WPStaging\Backup\BackupScheduler;
use WPStaging\Framework\BackgroundProcessing\BackgroundProcessingServiceProvider;
use WPStaging\Framework\BackgroundProcessing\FeatureDetection;
use WPStaging\Framework\BackgroundProcessing\QueueProcessor;

use function WPStaging\functions\debug_log;

/**
 * Per-request cron integrity check.
 *
 * Detects WP Staging cron events that should be registered but are missing
 * (or corrupted) in WordPress' cron option, and repairs them. Covers:
 *
 *  - Static recurring events (daily/weekly maintenance, queue maintenance,
 *    queue process, ajax feature detection) — missing events are re-scheduled;
 *    events registered with the wrong recurrence are re-scheduled with the
 *    correct one.
 *  - Dynamic per-schedule backup events in `wpstg_backup_schedules` — missing,
 *    orphaned (cron entry whose scheduleId no longer exists in the option), or
 *    registered with the wrong recurrence all trigger a full
 *    `BackupScheduler::reCreateCron()` rebuild.
 *
 * Throttled via a transient so the check (and any DB writes it triggers) runs
 * at most once every THROTTLE_SECONDS. Emits a diagnostic snapshot to the
 * debug log on every run so we can correlate cron failures (loopback
 * timeouts, DISABLE_WP_CRON, timezone drift) with users' bug reports.
 *
 * The whole run is wrapped in a top-level try/catch: since this fires on
 * `init`, a crash here would break every request for every plugin.
 */
class CronIntegrity
{
    /** @var string */
    const TRANSIENT_INTEGRITY_CHECKED = 'wpstg.cron.integrity_checked';

    /** @var int */
    const THROTTLE_SECONDS = HOUR_IN_SECONDS;

    /** @var BackupScheduler */
    private $backupScheduler;

    /** @var bool */
    private $ranThisRequest = false;

    public function __construct(BackupScheduler $backupScheduler)
    {
        $this->backupScheduler = $backupScheduler;
    }

    /**
     * @return void
     */
    public function checkAndRepair()
    {
        if ($this->ranThisRequest) {
            return;
        }

        $this->ranThisRequest = true;

        // Per-blog transient by design: the backup schedules option (`wpstg_backup_schedules`)
        // and WordPress cron events are per-blog on multisite, so the throttle window is
        // also per-blog. On a large network this means the check runs once per blog per
        // THROTTLE_SECONDS, not once per network.
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

    /**
     * Static, always-on cron events registered by the plugin. Each entry is
     * [action, recurrence]. Two corruption modes are repaired here:
     *
     *  1. Event is missing → re-register.
     *  2. Event exists but registered under a different recurrence → clear
     *     and re-register with the correct recurrence.
     *
     * Repair is skipped when the owning feature is not booted on this
     * request (`has_action($action) === false`) so we don't re-create cron
     * events for features that are currently disabled or whose service
     * provider hasn't registered its callbacks. That avoids "ghost" events
     * that tick with no handler and that reappear after being intentionally
     * removed by the user.
     *
     * @return void
     */
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
                // Recurrence is not registered yet (e.g. Free mode does not have Cron::HOURLY).
                // Skip — the service provider that owns this event will not have registered it either.
                continue;
            }

            if (!$hasHandler) {
                // No callback is attached to this action on this request, so re-scheduling would
                // create a ghost event that fires with no listener. This also preserves user/admin
                // intent when a feature is disabled.
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

    /**
     * Three corruption modes for backup schedule crons:
     *
     *  1. Missing: schedule exists in the option but no matching cron entry.
     *  2. Orphaned: cron entry exists but its scheduleId is no longer in the
     *     option (e.g. a restore/migration left dangling crons).
     *  3. Wrong recurrence: cron entry exists for a scheduleId but is
     *     registered under a different recurrence than the option says.
     *
     * Any mode triggers a full `BackupScheduler::reCreateCron()`, which wipes
     * all `wpstg_create_cron_backup` entries and re-registers them from the
     * option (the source of truth).
     *
     * @return void
     */
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
            // No callback is attached to ACTION_CREATE_CRON_BACKUP on this request (e.g. REST/WP-CLI
            // where BackupServiceProvider::enqueueAjaxListeners has not run). Re-registering here
            // would create ghost events that tick with no listener, which is exactly what this
            // integrity check is meant to prevent. Defer repair to a request context that has the
            // handler attached.
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

    /**
     * Collect scheduleIds present in cron entries for ACTION_CREATE_CRON_BACKUP
     * that are no longer in the `wpstg_backup_schedules` option.
     *
     * @param array $schedules       Schedules from `wpstg_backup_schedules`.
     * @param array $scheduledByCron Output of `findScheduledBackupCrons()`.
     * @return string[]
     */
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

    /**
     * Walk `get_option('cron')` and collect currently scheduled
     * ACTION_CREATE_CRON_BACKUP entries keyed by scheduleId.
     *
     * Matching by scheduleId — rather than by `wp_next_scheduled(..., [$schedule])`
     * — is resilient to byte drift between the stored schedule array and the
     * args that were serialized at scheduling time (plugin upgrade / restore /
     * migration scenarios).
     *
     * @return array<string, array{recurrence: string|null, timestamp: int}>
     */
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
                // If the same scheduleId appears multiple times (shouldn't happen, but defensive),
                // keep the earliest one — reCreateCron() will consolidate them anyway.
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

    /**
     * @return array
     */
    private function buildEnvironmentSnapshot(): array
    {
        return [
            'serverTimeUtc'   => gmdate('Y-m-d H:i:s') . ' UTC',
            'siteTimezone'    => wp_timezone()->getName(),
            'disableWpCron'   => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
            'alternateWpCron' => defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON,
        ];
    }

    /**
     * @param array $schedules
     * @param array $scheduledByCron Output of `findScheduledBackupCrons()`.
     * @return array
     */
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
                // Only treat as "missing" (and thus repairable) if the recurrence is registered.
                // Otherwise reCreateCron() would be triggered every throttle window and fail
                // silently, producing churn for no benefit.
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
