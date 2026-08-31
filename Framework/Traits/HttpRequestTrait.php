<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Backup\Exceptions\StorageException;

trait HttpRequestTrait
{
    use ThrottledResponseTrait;











    protected function getRequestBody(string $url, array $args = [], bool $decodeBody = true)
    {
        $response = $this->getRemoteRequest($url, $args);
        $body     = wp_remote_retrieve_body($response);
        if ($decodeBody) {
            return json_decode($body, true);
        }

        return $body;
    }









    protected function getRemoteRequest(string $url, array $args = []): array
    {
        $defaults = [
            'timeout'     => 40,
            'httpversion' => '1.0',
            'sslverify'   => false,
            'method'      => 'GET',
        ];
        $args         = wp_parse_args($args, $defaults);
        $response     = $this->requestUntilNotThrottled($url, $args);
        $responseCode = wp_remote_retrieve_response_code($response);

        if (is_wp_error($response) || (!in_array($responseCode, [200, 201, 202, 204, 206, 302, 308]))) {
            $errorMessage = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);

            $xml = @simplexml_load_string($errorMessage);
            if ($xml !== false) {
                $errorMessage = (string)$xml->Message ?? (string)$xml->message ?? $errorMessage;
                if (!empty((string)$xml->Code) || !empty((string)$xml->code)) {
                    $errorMessage .= " (Code: " . ((string)$xml->Code ?? (string)$xml->code) . ")";
                }
            }

            throw new StorageException("Error Message: $errorMessage; Error Code: $responseCode; Url: $url", (int)$responseCode);
        }

        return $response;
    }
}
