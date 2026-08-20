<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Rest\Rest;

trait RestRequestTrait
{





    private $headers = [];

    private $verifySsl = false;






    private $isBlockingRequest = true;

 
    private $httpAuthUsername = '';

 
    private $httpAuthPassword = '';








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

        return wp_remote_post(
            $this->buildRequestUrl($url, $endpoint),
            $args
        );
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
