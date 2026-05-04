<?php

namespace WPStaging\Framework\Analytics;

use WPStaging\Framework\Analytics\Actions\AnalyticsGenericEvent;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Utils\Sanitize;

/**
 * Handles the AJAX endpoint for logging generic analytics events
 */
class AnalyticsGenericEventHandler
{
    /** @var Auth */
    private $auth;

    /** @var Sanitize */
    private $sanitize;

    /** @var AnalyticsConsent */
    private $analyticsConsent;

    public function __construct(Auth $auth, Sanitize $sanitize, AnalyticsConsent $analyticsConsent)
    {
        $this->auth             = $auth;
        $this->sanitize         = $sanitize;
        $this->analyticsConsent = $analyticsConsent;
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

        // Do not persist events when consent is missing to avoid unbounded queue growth.
        if (!$this->analyticsConsent->hasUserConsent()) {
            wp_send_json_success();
            return;
        }

        AnalyticsGenericEvent::logEvent($eventName, $groupName, $custom);

        wp_send_json_success();
    }

    /**
     * @param array $data
     * @return array<string, string>
     */
    private function sanitizeCustomData(array $data): array
    {
        $sanitized = [];

        // Hard cap processed payload size to keep this endpoint lightweight.
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
