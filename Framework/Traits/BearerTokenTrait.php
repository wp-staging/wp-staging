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
        $authHeader = $this->getAuthorizationHeaderFromRequest();
        if (empty($authHeader) || !preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            return $this->getAuthTokenFromPluginHeader();
        }

        return sanitize_text_field($matches[1]);
    }

    protected function getAuthTokenFromPluginHeader(): string
    {
        /**
         * Some hosts strip the Authorization header before PHP gets it.
         * We accept a plugin-specific fallback header that is set by WP STAGING clients.
         */
        $token = sanitize_text_field($_SERVER['HTTP_X_WPSTG_REQUEST'] ?? ''); // phpcs:ignore
        if (empty($token) || !preg_match('/^[a-f0-9]{12,}$/i', $token)) {
            throw new RuntimeException('Authorization header not found or invalid.', 401);
        }

        return $token;
    }

    private function getAuthorizationHeaderFromRequest(): string
    {
        $authHeader = '';
        if (function_exists('getallheaders')) {
            $authHeader = $this->extractAuthorizationHeader(getallheaders());
        }

        if (empty($authHeader) && function_exists('apache_request_headers')) {
            $authHeader = $this->extractAuthorizationHeader(apache_request_headers());
        }

        if (empty($authHeader) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = sanitize_text_field($_SERVER['HTTP_AUTHORIZATION']); // phpcs:ignore
        }

        return $authHeader;
    }

    /**
     * @param array<string, mixed> $headers
     */
    private function extractAuthorizationHeader(array $headers): string
    {
        foreach ($headers as $headerName => $headerValue) {
            if (strtolower((string)$headerName) !== 'authorization') {
                continue;
            }

            return sanitize_text_field((string)$headerValue);
        }

        return '';
    }
}
