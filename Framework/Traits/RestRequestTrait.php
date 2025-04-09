<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Rest\Rest;

trait RestRequestTrait
{
    /**
     * @param string $url
     * @param string $endpoint
     * @param array $body
     * @param string $accessToken
     * @return array|\WP_Error
     */
    protected function sendRestRequest(string $url, string $endpoint, array $body = [], string $accessToken = '')
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];
        if (!empty($accessToken)) {
            $headers['Authorization'] = 'Bearer ' . $accessToken;
        }

        $args = [
            'method'    => 'POST',
            'headers'   => $headers,
            'timeout'   => Rest::REQUEST_TIMEOUT,
            'sslverify' => false,
        ];
        if (!empty($body)) {
            $args['body'] = json_encode($body);
        }

        return wp_remote_post(
            $this->buildRequestUrl($url, $endpoint),
            $args
        );
    }

    /**
     * Build the full request URL with REST route
     *
     * @param string $url Base URL
     * @param string $endpoint REST endpoint path
     * @return string Complete URL
     */
    protected function buildRequestUrl(string $url, string $endpoint): string
    {
        return trailingslashit($url) . '?rest_route=/' . Rest::WPSTG_ROUTE_NAMESPACE_V1 . '/' . ltrim($endpoint, '/');
    }
}
