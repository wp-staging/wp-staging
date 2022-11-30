<?php

namespace WPStaging\Framework\Analytics;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\DebugLogReader;

abstract class AnalyticsEventDto implements \JsonSerializable
{
    use WithAnalyticsSiteInfo;

    /** @var string Which action is triggering this Analytics, eg: backup creation, staging push, etc */
    protected $event;

    /** @var string The Job ID or similar. */
    protected $job_identifier;

    /** @var string A unique hash that prevents duplicated events in a scenario where this database is restored in another site and the events are sent again. */
    protected $event_hash;

    /** @var bool Whether this job has naturally finished. Eg: Came to the expected ending. */
    protected $is_finished = false;

    /** @var bool Whether this job has started but did not finish during an expected time-frame. */
    protected $is_stale = false;

    /** @var bool Whether this job terminated in error. */
    protected $is_error = false;

    /** @var bool Whether this job has been cancelled by the user. */
    protected $is_cancelled = false;

    /** @var bool Whether the requirement check has failed for this job. */
    protected $is_requirement_check_fail = false;

    /** @var string The reason for the requirement check fail, if so. */
    protected $requirement_fail_reason = '';

    /** @var string The error message as shown in the front-end, if event terminated in error. */
    protected $error_message;

    /** @var string The last lines from the debug log file. */
    protected $last_debug_logs;

    /** @var bool An internal flag to check if this event is ready to be sent. */
    protected $ready_to_send = false;

    /** @var int A UNIX timestamp for when this event started. */
    protected $start_time;

    /** @var int A UNIX timestamp for when this event ended. */
    protected $end_time;

    /** @var int The duration in seconds between the start and end of the event. */
    protected $duration;

    /** @var array A collection of generic site information. */
    protected $site_info;

    public function __construct()
    {
        $this->event = $this->getEventAction();
        $this->site_info = $this->getAnalyticsSiteInfo();
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return string The name of this analytics event.
     */
    abstract public function getEventAction();

    public function enqueueStartEvent($jobId, $eventData)
    {
        $this->job_identifier = $jobId;
        $this->start_time = time();
        $this->event_hash = microtime(true) . rand();

        try {
            $this->saveEvent($jobId, $this);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not register start event analytics data for job ID $jobId.", 'debug');
        }
    }

    public function enqueueFinishEvent($jobId, $eventData, $eventOverrides = [])
    {
        try {
            $event = $this->getEventByJobId($jobId);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not register finish event analytics data for job ID $jobId", 'debug');

            return;
        }

        $event->is_finished = true;
        $event->end_time = time();
        $event->duration = time() - $event->start_time;
        $event->ready_to_send = true;

        // Allow concrete instances of this abstract class to modify this event.
        foreach ($eventOverrides as $key => $value) {
            $event->$key = $value;
        }

        try {
            $this->saveEvent($jobId, $event);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not save finish event analytics data for job ID $jobId.", 'debug');
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
            \WPStaging\functions\debug_log("WP STAGING: Could not register cancel event analytics data for job ID $jobId", 'debug');

            return;
        }

        // Early bail: Already cancelled
        if ($event->is_cancelled) {
            return;
        }

        /*
         * The Cancel routine may be called automatically when an error occurs
         * to perform cleanup tasks, so let's not register the cancel event
         * if this event is being triggered by a job that already has an error.
         */
        if ($event->is_error) {
            return;
        }

        $event->is_finished = false;
        $event->is_cancelled = true;
        $event->end_time = time();
        $event->duration = time() - $event->start_time;
        $event->ready_to_send = true;

        try {
            static::saveEvent($jobId, $event);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not save cancel event analytics data for job ID $jobId.", 'debug');
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
            \WPStaging\functions\debug_log("WP STAGING: Could not register cancel event analytics data for job ID $jobId", 'debug');

            return;
        }

        $lastDebugLogErrors = WPStaging::make(DebugLogReader::class)->getLastLogEntries(8 * KB_IN_BYTES);

        $event->is_finished = false;
        $event->is_error = true;
        $event->error_message = $errorMessage;
        $event->last_debug_logs = $lastDebugLogErrors;
        $event->end_time = time();
        $event->duration = time() - $event->start_time;
        $event->ready_to_send = true;

        try {
            static::saveEvent($jobId, $event);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not save cancel event analytics data for job ID $jobId.", 'debug');
        }
    }

    protected static function getEventByJobId($jobId)
    {
        if (!$event = get_option("wpstg_analytics_event_$jobId")) {
            throw new \UnexpectedValueException();
        }

        $event = json_decode($event);

        if (empty($event) || !is_object($event)) {
            throw new \UnexpectedValueException();
        }

        return $event;
    }

    protected static function saveEvent($jobId, $event)
    {
        $event = wp_json_encode($event);

        if (!update_option("wpstg_analytics_event_$jobId", $event, false)) {
            throw new \UnexpectedValueException();
        }
    }
}
