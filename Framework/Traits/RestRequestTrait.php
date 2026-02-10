<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Rest\Rest;

trait RestRequestTrait
{
    /**
     * Custom request headers sent to remote endpoint.
     *
     * @var array<string, string>
     */
    private $headers = [];

    private $verifySsl = false;

    /**
     * Fire-and-forget mode.
     * When false, requests use a tiny timeout and do not wait for a full response.
     * Use only when caller can safely continue without immediate remote result.
     */
    private $isBlockingRequest = true;

    /**
     * @param string $url
     * @param string $endpoint
     * @param array $body
     * @param string $accessToken
     * @return array|\WP_Error
     */
    protected function sendRestRequest(string $url, string $endpoint, array $body = [], string $accessToken = '')
    {
        $headers = $this->headers;
        $headers['Content-Type'] = 'application/json';
        if (!empty($accessToken)) {
            $headers['Authorization'] = 'Bearer ' . $accessToken;
        }

        $timeout = Rest::REQUEST_TIMEOUT;
        if (!$this->isBlockingRequest) {
            // Non-blocking call used to trigger remote background jobs.
            $timeout = 0.01;
        }

        $args = [
            'method'    => 'POST',
            'headers'   => $headers,
            'blocking'  => $this->isBlockingRequest,
            'timeout'   => $timeout,
            'sslverify' => $this->verifySsl,
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

    /**
     * @return void
     */
    protected function resetHeaders()
    {
        $this->headers = [];
    }

    /**
     * @param string $token
     * @return array<string, string>
     */
    protected function getAuthorizationHeader(string $token): array
    {
        return [
            'Authorization'   => 'Bearer ' . $token,
            'X-WPSTG-Request' => $token,
        ];
    }
}
