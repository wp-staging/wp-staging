<?php

namespace WPStaging\Framework\Traits;

use WP_Error;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Network\SsrfProtection;
use WPStaging\Framework\Rest\Rest;

trait RestRequestTrait
{





    private $headers = [];

    private $verifySsl = false;






    private $isBlockingRequest = true;

 
    private $httpAuthUsername = '';

 
    private $httpAuthPassword = '';






    private $isPeerSiteRequest = true;








    protected function sendRestRequest(string $url, string $endpoint, array $body = [], string $accessToken = '')
    {
        $headers = $this->headers;
        $headers['Content-Type'] = 'application/json';

        if (!empty($accessToken)) {
            $headers = array_merge($headers, $this->getAuthorizationHeader($accessToken));
        }

 
 
 
 
        if (!empty($this->httpAuthUsername) && !empty($this->httpAuthPassword)) {
            $headers['Authorization'] = 'Basic ' . base64_encode($this->httpAuthUsername . ':' . $this->httpAuthPassword);
        }

        $timeout = Rest::REQUEST_TIMEOUT;
        if (!$this->isBlockingRequest) {
 
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

        $requestUrl = $this->buildRequestUrl($url, $endpoint);
        if (!$this->isPeerSiteRequest) {
            return wp_remote_post($requestUrl, $args);
        }

        $ssrfProtection = WPStaging::make(SsrfProtection::class);
        if ($ssrfProtection->isBlockedUrl($requestUrl)) {
            return $this->buildBlockedPeerSiteError($requestUrl);
        }

        $args['redirection'] = 0;

        $response = $ssrfProtection->runRequestWithPinnedIp($requestUrl, function () use ($requestUrl, $args) {
            return wp_remote_post($requestUrl, $args);
        });

        $statusCode       = (int)wp_remote_retrieve_response_code($response);
        $redirectLocation = wp_remote_retrieve_header($response, 'location');
        if ($statusCode >= 300 && $statusCode < 400 && is_string($redirectLocation) && $redirectLocation !== '') {
            return $this->buildPeerSiteRedirectError($requestUrl, $redirectLocation);
        }

        return $response;
    }






    private function buildPeerSiteRedirectError(string $requestUrl, string $redirectLocation): WP_Error
    {
        return new WP_Error('wpstg_peer_site_redirect', sprintf(
            /* translators: 1: URL the request was sent to, 2: URL the remote site redirects it to */
            esc_html__('The remote site redirects %1$s to %2$s. Correct its Site Address and copy the connection key again.', 'wp-staging'),
            esc_html($requestUrl),
            esc_html($redirectLocation)
        ));
    }





    private function buildBlockedPeerSiteError(string $requestUrl): WP_Error
    {
        return new WP_Error('wpstg_blocked_url', sprintf(
            /* translators: %s: host name of the remote site */
            esc_html__('The remote site %s could not be resolved, or resolves to a loopback or link-local address. If both sites intentionally run on the same server, define WPSTG_DISABLE_SSRF_PROTECTION as true in wp-config.php on both sites to allow it.', 'wp-staging'),
            esc_html((string)parse_url($requestUrl, PHP_URL_HOST))
        ));
    }








    protected function buildRequestUrl(string $url, string $endpoint): string
    {
        return trailingslashit($url) . '?rest_route=/' . Rest::WPSTG_ROUTE_NAMESPACE_V1 . '/' . ltrim($endpoint, '/');
    }




    protected function resetHeaders()
    {
        $this->headers = [];
    }






    protected function setHttpAuth(string $username, string $password)
    {
        if (empty($username) || empty($password)) {
            $this->httpAuthUsername = '';
            $this->httpAuthPassword = '';

            return;
        }

        $this->httpAuthUsername = $username;
        $this->httpAuthPassword = $password;
    }





    protected function getAuthorizationHeader(string $token): array
    {
        return [
            'Authorization'   => 'Bearer ' . $token,
            'X-WPSTG-Request' => $token,
        ];
    }
}
