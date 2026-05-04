<?php

namespace WPStaging\Framework\Analytics\Actions;

use WPStaging\Framework\Analytics\AnalyticsEventDto;

/**
 * Lightweight, single-shot analytics event for tracking generic actions
 * that don't follow the job lifecycle (start → finish/error/cancel).
 *
 * @example
 * AnalyticsGenericEvent::logEvent('feature_used', 'backup', ['source' => 'toolbar']);
 */
class AnalyticsGenericEvent extends AnalyticsEventDto
{
    /** @var string */
    public $event_name;

    /** @var string */
    public $group_name;

    /** @var array<string, scalar> */
    public $custom;

    /** @var int UNIX timestamp when the event was created. */
    public $created_at;

    /**
     * @return string
     */
    public function getEventAction()
    {
        return 'event_generic';
    }

    /**
     * Serialize only the generic-event fields required by analytics.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $data = [
            'event'         => $this->event,
            'ready_to_send' => $this->ready_to_send,
            'site_info'     => $this->site_info,
            'event_name'    => $this->event_name,
            'event_hash'    => $this->event_hash,
            'created_at'    => $this->created_at,
        ];

        if (!empty($this->group_name)) {
            $data['group_name'] = $this->group_name;
        }

        if (!empty($this->custom)) {
            $data['custom'] = $this->custom;
        }

        return $data;
    }

    /**
     * Log a generic analytics event immediately in database for later sending.
     *
     * @param string $eventName The event name (e.g. 'feature_used')
     * @param string $groupName Optional grouping label (e.g. 'backup')
     * @param array<string, scalar> $custom Optional key/value custom data
     */
    public static function logEvent(string $eventName, string $groupName = '', array $custom = [])
    {
        $event = new self();
        $jobId = 'generic_' . uniqid('', true);

        $event->event_name     = $eventName;
        $event->event_hash     = microtime(true) . rand();
        $event->created_at     = time();
        $event->ready_to_send  = true;

        if ($groupName !== '') {
            $event->group_name = $groupName;
        }

        if (!empty($custom)) {
            $event->custom = $custom;
        }

        try {
            static::saveEvent($jobId, $event);
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log(
                "WP STAGING: Could not save generic analytics event '$eventName'.",
                'debug',
                false
            );
        }
    }
}
