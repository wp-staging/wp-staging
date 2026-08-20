<?php

namespace WPStaging\Framework\Analytics\Actions;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\AnalyticsEventDto;
use WPStaging\Framework\Analytics\AnalyticsSender;








class AnalyticsGenericEvent extends AnalyticsEventDto
{
 
    public $event_name;

 
    public $group_name;

 
    public $custom;

 
    public $created_at;




    public function getEventAction()
    {
        return 'event_generic';
    }






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

        if (!empty($this->experiment)) {
            $data['experiment'] = $this->experiment;
            $data['variant']    = $this->variant;
        }

        return $data;
    }














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












    public static function logEventNow(string $eventName, string $groupName = '', array $custom = [])
    {
        try {
            $event = new self();

            $event->event_name    = $eventName;
            $event->event_hash    = microtime(true) . rand();
            $event->created_at    = time();
            $event->ready_to_send = true;

            if ($groupName !== '') {
                $event->group_name = $groupName;
            }

            if (!empty($custom)) {
                $event->custom = $custom;
            }

            WPStaging::make(AnalyticsSender::class)->sendNow($event);
        } catch (\Throwable $e) {
            \WPStaging\functions\debug_log(
                "WP STAGING: Could not send analytics event '$eventName'.",
                'debug',
                false
            );
        }
    }
}
