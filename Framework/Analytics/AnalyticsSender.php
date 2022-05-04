<?php

namespace WPStaging\Framework\Analytics;

class AnalyticsSender
{
    use WithAnalyticsAPI;

    protected $consent;

    private $corruptSettingsNotice;

    public function __construct(AnalyticsConsent $consent)
    {
        $this->consent = $consent;
    }

    public function maybeSend()
    {
        // Early bail: Do not run on AJAX requests.
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        // We store the analytics sending time at "wpstg_settings"
        // since this runs on every request, and it's an autoloaded option.
        // Also, the value we need to store in it is small.
        $settings = get_option("wpstg_settings", []);

        // convert settings from type object to array
        if (is_object($settings)) {
            $settings = json_decode(json_encode($settings), true);
        }

        // If still $settings is not array, bail
        if (!is_array($settings)) {
            return;
        }

        // Interval to wait before sending events.
        $interval = 15 * MINUTE_IN_SECONDS;

        // Early bail: Sent not so long ago.
        if (isset($settings['lastAnalyticsSend']) && time() - $settings['lastAnalyticsSend'] - $interval < 0) {
            return;
        }

        $settings['lastAnalyticsSend'] = time();

        if (!update_option('wpstg_settings', $settings)) {
            \WPStaging\functions\debug_log('WP STAGING: Could not update Analytics last sent time.', 'debug');
        };

        $this->sendAnalytics();
    }

    public function sendAnalytics()
    {
        global $wpdb;

        $eventOptions = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE `option_name` LIKE 'wpstg_analytics_event_%' LIMIT 0, 20");

        // Nothing to send.
        if (empty($eventOptions)) {
            return;
        }

        // Early bail: User has not given consent to send analytics
        if (!$this->consent->hasUserConsent()) {
            return;
        }

        // Format the events to the expected format
        $events = array_map(function ($eventOption) {
            return json_decode($eventOption->option_value);
        }, $eventOptions);

        $this->setStaleEvents($events);

        // Filter the events to send only those that are ready to be sent
        $events = array_filter($events, function ($event) {
            return $event->ready_to_send;
        });

        // Early bail: No events ready to be sent
        if (empty($events)) {
            return;
        }

        // Convert true to 1 and false to 0 for MySQL TinyInt
        foreach ($events as &$event) {
            foreach ($event as $property => &$value) {
                if (is_bool($value)) {
                    $event->$property = (int)$value;
                } elseif ($property === 'site_info') {
                    $siteInfo = &$value;

                    foreach ($siteInfo as $siteInfoProperty => &$siteInfoValue) {
                        if (is_bool($siteInfoValue)) {
                            $siteInfo->$siteInfoProperty = (int)$siteInfoValue;
                        }
                    }
                }
            }
        }

        // Delete the events, regardless of whether it succeeded or failed to send.
        // This prevents events from hanging and being sent every time if they are in an invalid format or something goes wrong.
        $idsToDelete = implode(',', array_map(function ($eventOption) {
            return $eventOption->option_id;
        }, $eventOptions));

        if (!$wpdb->query("DELETE FROM $wpdb->options WHERE `option_id` IN ($idsToDelete)")) {
            \WPStaging\functions\debug_log('WP STAGING Analytics Delete Sent Events Error: ' . $wpdb->last_error);
        }

        $body = wp_json_encode([
            'events' => $events,
            'site_hash' => $this->getSiteHash(),
        ]);

        $url = $this->getApiUrl('events');

        // Early bail: Do not dispatch events when in dev mode, unless allowed.
        if (defined('WPSTG_DEV') && WPSTG_DEV) {
            if (!defined('WPSTG_DEV_SEND_ANALYTICS') || defined('WPSTG_DEV_SEND_ANALYTICS') && !WPSTG_DEV_SEND_ANALYTICS) {
                return;
            }
        }
        $response = wp_remote_post($url, [
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body' => $body,
            'data_format' => 'body',
            'timeout' => 10,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'sslverify' => false,
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            if (wp_remote_retrieve_response_code($response) == 412) {
                // The site hash does not exist in the Analytics database. We need to ask for consent again.
                if ($this->consent->hasUserConsent()) {
                    try {
                        $this->consent->giveConsent();
                    } catch (\Exception $e) {
                        // We could not re-validate the consent.
                        // Let's ask it again to the user, which will handle notices and a scenario of connection failure.
                        $this->consent->invalidateConsent();
                    }
                }
            }

            $errorMessage = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
            \WPStaging\functions\debug_log('WP STAGING Analytics Send Error: ' . $errorMessage);
        }
    }

    /**
     * Mark as stale the events that started longer than 1 day ago but isn't ready to send yet
     *
     * @param $events
     */
    protected function setStaleEvents(&$events)
    {
        foreach ($events as &$event) {
            if (!$event->ready_to_send && $event->start_time < time() - 1 * DAY_IN_SECONDS) {
                $event->ready_to_send = true;
                $event->is_stale = true;
            }
        }
    }
}
