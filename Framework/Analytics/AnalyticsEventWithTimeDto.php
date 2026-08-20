<?php

namespace WPStaging\Framework\Analytics;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\DebugLogReader;

abstract class AnalyticsEventWithTimeDto extends AnalyticsEventDto
{
 
    protected $finished_at = null;

 
    protected $stale_at = null;

 
    protected $error_at = null;

 
    protected $cancelled_at = null;

 
    protected $start_at = null;

    public function enqueueStartEvent($jobId, $eventData)
    {
        $this->job_identifier = $jobId;
        $this->start_at = time();
        $this->event_hash = microtime(true) . rand();

        try {
            $this->saveEvent($jobId, $this);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not register start event analytics data for job ID $jobId.", 'debug', false);
        }
    }

    public function enqueueFinishEvent($jobId, $eventData, $eventOverrides = [])
    {
        try {
            $event = $this->getEventByJobId($jobId);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not register finish event analytics data for job ID $jobId", 'debug', false);

            return;
        }

        $event->finished_at = time();
        $event->duration = time() - $event->start_at;
        $event->ready_to_send = true;

 
        foreach ($eventOverrides as $key => $value) {
            $event->$key = $value;
        }

        try {
            $this->saveEvent($jobId, $event);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not save finish event analytics data for job ID $jobId.", 'debug', false);
        }
    }




    public static function enqueueCancelEvent($jobId)
    {
        try {
            $event = static::getEventByJobId($jobId);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not register cancel event analytics data for job ID $jobId", 'debug', false);

            return;
        }

 
        if ($event->cancelled_at) {
            return;
        }






        if ($event->error_at) {
            return;
        }

        $event->finished_at = null;
        $event->cancelled_at = time();
        $event->duration = time() - $event->start_at;
        $event->ready_to_send = true;

        try {
            static::saveEvent($jobId, $event);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not save cancel event analytics data for job ID $jobId.", 'debug', false);
        }
    }




    public static function enqueueErrorEvent($jobId, $errorMessage, string $errorCode = '')
    {
        try {
            $event = static::getEventByJobId($jobId);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not register cancel event analytics data for job ID $jobId", 'debug', false);

            return;
        }

        $lastDebugLogErrors = WPStaging::make(DebugLogReader::class)->getLastLogEntries(8 * KB_IN_BYTES);

        $event->finished_at = null;
        $event->error_at = time();
        $event->error_message = $errorMessage;
        $event->error_code = ErrorCode::sanitize($errorCode);
        $event->last_debug_logs = $lastDebugLogErrors;
        $event->duration = time() - $event->start_at;
        $event->ready_to_send = true;

        try {
            static::saveEvent($jobId, $event);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not save cancel event analytics data for job ID $jobId.", 'debug', false);
        }
    }
}
