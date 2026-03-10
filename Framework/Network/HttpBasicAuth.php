<?php

namespace WPStaging\Framework\Network;

use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\Facades\DataEncryption;

/**
 * Provides HTTP Basic Authentication headers for loopback requests
 *
 * When wp-admin is protected by HTTP Basic Auth (e.g. via Plesk or .htpasswd),
 * loopback requests to admin-ajax.php and wp-cron.php need Authorization headers.
 * This trait reads stored credentials and builds the header array.
 */
trait HttpBasicAuth
{
    /**
     * Returns the Authorization header array for wp_remote_* requests,
     * or an empty array when no credentials are configured.
     *
     * @return array<string, string>
     */
    protected function getHttpAuthHeaders(): array
    {
        $credentials = get_option(Queue::OPTION_HTTP_AUTH_CREDENTIALS, []);

        if (
            !is_array($credentials)
            || empty($credentials['username'])
            || empty($credentials['password'])
        ) {
            return [];
        }

        $password = DataEncryption::decrypt($credentials['password']);

        return [
            'Authorization' => 'Basic ' . base64_encode($credentials['username'] . ':' . $password),
        ];
    }
}
