<?php

namespace WPStaging\Framework\Analytics;

use WPStaging\Framework\Analytics\Actions\AnalyticsGenericEvent;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Utils\Sanitize;




class AnalyticsGenericEventHandler
{
 
    private $auth;

 
    private $sanitize;

    public function __construct(Auth $auth, Sanitize $sanitize)
    {
        $this->auth     = $auth;
        $this->sanitize = $sanitize;
    }

    public function ajaxHandleGenericEvent()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            wp_send_json_error(null, 401);
            return;
        }

        $eventName = isset($_POST['event_name']) ? $this->sanitize->sanitizeString($_POST['event_name']) : '';
        if ($eventName === '' || !preg_match('/^[a-zA-Z0-9_]{1,100}$/', $eventName)) {
            wp_send_json_error(null, 400);
            return;
        }

        $groupName = isset($_POST['group_name']) ? $this->sanitize->sanitizeString($_POST['group_name']) : '';
        if ($groupName !== '' && !preg_match('/^[a-zA-Z0-9_]{1,100}$/', $groupName)) {
            wp_send_json_error(null, 400);
            return;
        }

        $custom = isset($_POST['custom']) ? $this->sanitizeCustomData($this->sanitize->sanitizeArrayString($_POST['custom'])) : [];

        AnalyticsGenericEvent::logEvent($eventName, $groupName, $custom);

        wp_send_json_success();
    }





    private function sanitizeCustomData(array $data): array
    {
        $sanitized = [];

 
        $data = array_slice($data, 0, 20, true);

        foreach ($data as $key => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $key = mb_substr((string)$key, 0, 100);
            $key = $this->sanitize->sanitizeString($key);
            if ($key === '') {
                continue;
            }

            $sanitized[$key] = mb_substr($this->sanitize->sanitizeString((string)$value), 0, 500);
        }

        return $sanitized;
    }
}
