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
     * @return string|array By default the request's response body will be decoded and return as array,
     *                      to return the response body as string, pass the decodeBody param as false.
     */
    protected function getRequestBody(string $url, array $args = [], bool $decodeBody = true)
    {
        $response = $this->getRemoteRequest($url, $args);
        $body     = wp_remote_retrieve_body($response);
        if ($decodeBody) {
            return json_decode($body, true);
        }

        return $body;
    }

    /**
     * @param string $url.
     * @param array  $args Optional. Request arguments. Default empty array.
     *                 See https://developer.wordpress.org/reference/classes/WP_Http/request/ for information on accepted arguments.
     *
     * @throws StorageException
     * @return array The response array
     */
    protected function getRemoteRequest(string $url, array $args = []): array
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

        if (is_wp_error($response) || !in_array($responseCode, [200, 201, 202, 204, 206, 302, 308])) {
            $errorMessage = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);

            throw new StorageException("Error in remote request! Url: $url; Error Message: $errorMessage; Error Code: $responseCode;");
        }

        return $response;
    }
}
