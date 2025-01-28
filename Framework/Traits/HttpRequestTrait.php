<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Backup\Exceptions\StorageException;

trait HttpRequestTrait
{
    /**
     * @param string $url.
     * @param array  $args Optional. Request arguments. Default empty array.
     *                 See https://developer.wordpress.org/reference/classes/WP_Http/request/ for information on accepted arguments.
     * @param bool $decodeBody Optional. If true the body will be decoded using json_decode.
     *
     * @throws StorageException
     * @return mixed
     */
    protected function getRequestBody(string $url, array $args = [], bool $decodeBody = true)
    {
        $defaults = [
            'timeout'     => 40,
            'httpversion' => '1.0',
            'sslverify'   => false,
            'method'      => 'GET'
        ];
        $args         = wp_parse_args($args, $defaults);
        $response     = wp_remote_request($url, $args);
        $responseCode = wp_remote_retrieve_response_code($response);
        $body         = wp_remote_retrieve_body($response);

        if (is_wp_error($response) || !in_array($responseCode, [200, 201, 202, 204, 206, 302])) {
            $errorMessage = is_wp_error($response) ? $response->get_error_message() : $body;
            $this->error  = $errorMessage;

            throw new StorageException("Error in remote request! Url: $url; Error Message: $errorMessage");
        }

        if ($decodeBody) {
            return json_decode($body, true);
        }

        return $body;
    }
}
