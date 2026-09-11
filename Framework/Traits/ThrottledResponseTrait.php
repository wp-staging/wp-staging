<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Backup\Exceptions\UploadThrottledException;








trait ThrottledResponseTrait
{




    protected function isThrottledResponse($response): bool
    {
        if (is_wp_error($response)) {
            return false;
        }

        $responseCode = (int)wp_remote_retrieve_response_code($response);
        if (in_array($responseCode, [429, 503], true)) {
            return true;
        }

        if ($responseCode !== 403) {
            return false;
        }

        return $this->reportsRateLimit(wp_remote_retrieve_body($response));
    }





    protected function reportsRateLimit(string $message): bool
    {
        $reasons = [
            'userRateLimitExceeded',
            'rateLimitExceeded',
            'SlowDown',
            'too_many_requests',
            'too_many_write_operations',
        ];

        foreach ($reasons as $reason) {
            if (stripos($message, $reason) !== false) {
                return true;
            }
        }

        return false;
    }








    protected function requestUntilNotThrottled(string $url, array $args, bool $deferUpload = false)
    {
        $response = wp_remote_request($url, $args);

        $isDropboxWriteLimit = !is_wp_error($response) && (int)wp_remote_retrieve_response_code($response) === 409 && $this->reportsRateLimit(wp_remote_retrieve_body($response));
        if ($deferUpload && ($this->isThrottledResponse($response) || $isDropboxWriteLimit)) {
            $retryAfter = wp_remote_retrieve_header($response, 'retry-after');
            $retryAfter = is_array($retryAfter) ? reset($retryAfter) : $retryAfter;
            throw new UploadThrottledException((string)$retryAfter);
        }

        for ($attempt = 1; $attempt <= $this->getMaxThrottleRetries(); $attempt++) {
            if (!$this->isThrottledResponse($response)) {
                return $response;
            }

            if (!$this->waitForThrottleToPass($response, $attempt)) {
                return $response;
            }

            $response = wp_remote_request($url, $args);
        }

        return $response;
    }






    protected function postUntilNotThrottled(string $url, array $args, bool $deferUpload = false)
    {
        $args['method'] = 'POST';

        return $this->requestUntilNotThrottled($url, $args, $deferUpload);
    }




    protected function getMaxThrottleRetries(): int
    {
        return 3;
    }





    protected function getRequestedWaitSeconds($response): int
    {
        if (is_wp_error($response)) {
            return 0;
        }

        $retryAfter = wp_remote_retrieve_header($response, 'retry-after');
        if (is_array($retryAfter)) {
            $retryAfter = reset($retryAfter);
        }

        return (int)$retryAfter;
    }








    protected function waitForThrottleToPass($response, int $attempt): bool
    {
        $backoffSeconds = 2;
        $maxWaitSeconds = 10;

        $requestedWait = $this->getRequestedWaitSeconds($response);
        $waitSeconds   = $requestedWait > 0 ? $requestedWait : $attempt * $backoffSeconds;

        if ($waitSeconds > $maxWaitSeconds) {
            return false;
        }

        sleep($waitSeconds);

        return true;
    }
}
