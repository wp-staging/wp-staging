<?php

namespace WPStaging\Framework\Analytics;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Experiments\ExperimentManager;
use WPStaging\Framework\Filesystem\DebugLogReader;

abstract class AnalyticsEventDto implements \JsonSerializable
{
    use WithAnalyticsSiteInfo;

 
    protected $event;

 
    protected $job_identifier;

 
    protected $event_hash;

 
    protected $is_finished = false;

 
    protected $is_stale = false;

 
    protected $is_error = false;

 
    protected $is_cancelled = false;

 
    protected $is_requirement_check_fail = false;

 
    protected $requirement_fail_reason = '';

 
    protected $error_message;

 
    protected $error_code;

 
    protected $last_debug_logs;

 
    protected $ready_to_send = false;

 
    protected $start_time;

 
    protected $end_time;

 
    protected $duration;

 
    protected $site_info;

 
    protected $experiment;

 
    protected $variant;

    public function __construct()
    {
        $this->event = $this->getEventAction();
        $this->site_info = $this->getAnalyticsSiteInfo();

 
 
        $attribution = WPStaging::make(ExperimentManager::class)->getAttribution();

        $this->experiment = isset($attribution['experiment']) ? $attribution['experiment'] : null;
        $this->variant    = isset($attribution['variant']) ? $attribution['variant'] : null;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }




    abstract public function getEventAction();

    public function enqueueStartEvent($jobId, $eventData)
    {
        $this->job_identifier = $jobId;
        $this->start_time = time();
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

        $event->is_finished = true;
        $event->end_time = time();
        $event->duration = time() - $event->start_time;
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

 
        if ($event->is_cancelled) {
            return;
        }






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

        $event->is_finished = false;
        $event->is_error = true;
        $event->error_message = $errorMessage;
        $event->error_code = ErrorCode::sanitize($errorCode);
        $event->last_debug_logs = $lastDebugLogErrors;
        $event->end_time = time();
        $event->duration = time() - $event->start_time;
        $event->ready_to_send = true;

        try {
            static::saveEvent($jobId, $event);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log("WP STAGING: Could not save cancel event analytics data for job ID $jobId.", 'debug', false);
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







    protected function getEventDataValue($eventData, string $key, $default = null)
    {
        if (is_array($eventData) && array_key_exists($key, $eventData)) {
            return $eventData[$key];
        }

        if (!is_object($eventData)) {
            return $default;
        }

        $publicProperties = get_object_vars($eventData);
        if (array_key_exists($key, $publicProperties)) {
            return $publicProperties[$key];
        }

        $getter = 'get' . ucfirst($key);
        if (method_exists($eventData, $getter)) {
            return $eventData->{$getter}();
        }

        $isser = 'is' . ucfirst($key);
        if (method_exists($eventData, $isser)) {
            return $eventData->{$isser}();
        }

        return $default;
    }

    protected function getStagingEngine($eventData): string
    {
        $engine = $this->getEventDataValue($eventData, 'stagingEngine', 'legacy');

        return in_array($engine, ['legacy', 'next_gen'], true) ? $engine : 'legacy';
    }
}
