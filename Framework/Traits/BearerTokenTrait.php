<?php

namespace WPStaging\Framework\Traits;

use RuntimeException;

/**
 * Provide method to get bearer tokens.
 */
trait BearerTokenTrait
{
    protected function getBearerToken(): string
    {
        $authHeader = '';

        if (function_exists('getallheaders')) {
            $headers    = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
        }

        if (function_exists('apache_request_headers')) {
            $headers    = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? '';
        }

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = sanitize_text_field($_SERVER['HTTP_AUTHORIZATION']); // phpcs
        }

        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            throw new RuntimeException('Missing or invalid Authorization header');
        }

        return $matches[1];
    }
}
