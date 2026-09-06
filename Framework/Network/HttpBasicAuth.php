<?php

namespace WPStaging\Framework\Network;

use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\Facades\DataEncryption;




trait HttpBasicAuth
{






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







    protected function getLoginRelatedCookies(): array
    {
        if (empty($_COOKIE) || !is_array($_COOKIE)) {
            return [];
        }

        $allowed = [];
        foreach ($_COOKIE as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

 
            if (!preg_match('/^wordpress_(?:logged_in_|sec_)?[a-f0-9]{32}$/', $name)) {
                continue;
            }

            if (is_scalar($value)) {
                $allowed[$name] = (string)$value;
            }
        }

        return $allowed;
    }
}
