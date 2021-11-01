<?php

namespace WPStaging\Framework\Analytics;

trait WithAnalyticsAPI
{
    protected function getApiUrl($endpoint)
    {
        if (defined('WPSTG_DEV') && WPSTG_DEV) {
            $url = 'http://analytics.local:8080';
        } else {
            $url = 'https://analytics.wp-staging.com';
        }

        return $url . '/' . $endpoint;
    }

    /**
     * We use the hash of the salt as the identifier, this will only change if the salts changes.
     *
     * @return string
     */
    protected function getSiteHash()
    {
        $hostName = parse_url(get_site_url());

        if (is_array($hostName) && array_key_exists('host', $hostName)) {
            $hostName = $hostName['host'];
        } else {
            $hostName = '';
        }

        if (defined('AUTH_SALT') && !empty(AUTH_SALT) && AUTH_SALT !== 'put your unique phrase here') {
            $authSalt = AUTH_SALT;
        } else {
            if (!$authSalt = get_option('wpstg_analytics_fallback_site_hash')) {
                $authSalt = wp_generate_password(32);
                update_option('wpstg_analytics_fallback_site_hash', $authSalt);
            }
        }

        return wp_hash($authSalt . $hostName);
    }
}
