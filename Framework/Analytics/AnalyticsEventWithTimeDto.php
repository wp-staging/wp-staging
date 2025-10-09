<?php

namespace WPStaging\Framework\Analytics;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\DebugLogReader;

abstract class AnalyticsEventWithTimeDto extends AnalyticsEventDto
{
    /** @var int UNIX timestamp whether this job has naturally finished. Eg: Came to the expected ending. */
    protected $finished_at = null;

    /** @var int UNIX timestamp whether this job has started but did not finish during an expected time-frame. */
    protected $stale_at = null;

    /** @var int UNIX timestamp whether this job terminated in error. */
    protected $error_at = null;

    /** @var int UNIX timestamp whether this job has been cancelled by the user. */
    protected $cancelled_at = null;

    /** @var int A UNIX timestamp for when this event started. */
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

        // Allow concrete instances of this abstract class to modify this event.
        foreach ($eventOverrides as $key => $value) {
            $event->$key = $value;
        }

        try {
            $this->saveEvent($jobId, $event);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not save finish event analytics data for job ID $jobId.", 'debug', false);
        }
    }

    /**
     * Cancel event is static as it's a generic event not related to any specific type of event.
     */
    public static function enqueueCancelEvent($jobId)
    {
        try {
            $event = static::getEventByJobId($jobId);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not register cancel event analytics data for job ID $jobId", 'debug', false);

            return;
        }

        // Early bail: Already cancelled
        if ($event->cancelled_at) {
            return;
        }

        /*
         * The Cancel routine may be called automatically when an error occurs
         * to perform cleanup tasks, so let's not register the cancel event
         * if this event is being triggered by a job that already has an error.
         */
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

    /**
     * Error event is static as it's a generic event not related to any specific type of event.
     */
    public static function enqueueErrorEvent($jobId, $errorMessage)
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
