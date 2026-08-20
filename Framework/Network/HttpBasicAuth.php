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
}
