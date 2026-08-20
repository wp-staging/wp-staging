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
 
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

 
 
 
        $settings = get_option("wpstg_settings", []);

 
        if (is_object($settings)) {
            $settings = json_decode(json_encode($settings), true);
        }

 
        if (!is_array($settings)) {
            return;
        }

 
        $interval = ($this->isDev() ? 1 : 15) * MINUTE_IN_SECONDS;

 
        if (isset($settings['lastAnalyticsSend']) && time() - $settings['lastAnalyticsSend'] - $interval < 0) {
            return;
        }

        $settings['lastAnalyticsSend'] = time();

        if (!update_option('wpstg_settings', $settings)) {
            \WPStaging\functions\debug_log('WP STAGING: Could not update Analytics last sent time.', 'debug');
        };

        $this->sendAnalytics();
    }



















    public function sendNow($event): bool
    {
        if (!$this->consent->hasUserConsent()) {
            return false;
        }

        if ($this->isDev() && !$this->canDevSendAnalytics()) {
            return false;
        }

        $response = wp_remote_post($this->getApiUrl('events'), [
            'method'      => 'POST',
            'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
            'body'        => wp_json_encode([
                'events'    => [$event],
                'site_hash' => $this->getSiteHash(),
            ]),
            'data_format' => 'body',
            'timeout'     => 1,
            'redirection' => 0,
            'httpversion' => '1.0',
            'blocking'    => false,
            'sslverify'   => false,
        ]);

        return !is_wp_error($response);
    }

    public function sendAnalytics()
    {
        global $wpdb;

        $eventOptions = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE `option_name` LIKE 'wpstg_analytics_event_%' LIMIT 0, 20");

 
        if (empty($eventOptions)) {
            return;
        }

 
        if (!$this->consent->hasUserConsent()) {
            return;
        }

 
        $events = array_map(function ($eventOption) {
            return json_decode($eventOption->option_value);
        }, $eventOptions);

        $this->setStaleEvents($events);

 
        $events = array_filter($events, function ($event) {
            return $event->ready_to_send;
        });

 
        if (empty($events)) {
            return;
        }

 
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

 
 
        $idsToDelete = implode(',', array_map(function ($key) use ($eventOptions) {
            return $eventOptions[$key]->option_id;
        }, array_keys($events)));

        if (!$wpdb->query("DELETE FROM $wpdb->options WHERE `option_id` IN ($idsToDelete)")) {
            \WPStaging\functions\debug_log('WP STAGING Analytics Delete Sent Events Error: ' . $wpdb->last_error);
        }

 
        $events = array_values($events);

        $body = wp_json_encode([
            'events'    => $events,
            'site_hash' => $this->getSiteHash(),
        ]);

        $url = $this->getApiUrl('events');

 
        if ($this->isDev() && !$this->canDevSendAnalytics()) {
            return;
        }

        $response = wp_remote_post($url, [
            'method'      => 'POST',
            'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
            'body'        => $body,
            'data_format' => 'body',
            'timeout'     => 10,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'sslverify'   => false,
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            if (wp_remote_retrieve_response_code($response) == 412) {
 
                if ($this->consent->hasUserConsent()) {
                    try {
                        $this->consent->giveConsent();
                    } catch (\Exception $e) {
 
 
 
 
 
                    }
                }
            }

            $errorMessage = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
            \WPStaging\functions\debug_log('WP STAGING Analytics Send Error: ' . $errorMessage);
        }
    }






    protected function setStaleEvents(&$events)
    {
        $staleThreshold = time() - 1 * DAY_IN_SECONDS;

        foreach ($events as &$event) {
            if ($event->ready_to_send) {
                continue;
            }

 
            if (isset($event->start_at)) {
                if ($event->start_at < $staleThreshold) {
                    $event->ready_to_send = true;
                    $event->stale_at = time();
                }

                continue;
            }

 
 
            if (!empty($event->start_time) && $event->start_time < $staleThreshold) {
                $event->ready_to_send = true;
                $event->is_stale = true;
            }
        }
    }

    protected function isDev(): bool
    {
        return defined('WPSTG_IS_DEV') && WPSTG_IS_DEV;
    }

    protected function canDevSendAnalytics(): bool
    {
        return defined('WPSTG_DEV_SEND_ANALYTICS') && WPSTG_DEV_SEND_ANALYTICS;
    }
}
