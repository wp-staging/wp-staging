<?php

/**
 * Provides methods to be aware of the queue system and its inner workings.
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */

namespace WPStaging\Framework\BackgroundProcessing;

use WP_Error;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Network\HttpBasicAuth;

use function WPStaging\functions\debug_log;

/**
 * Trait WithQueueAwareness
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */
trait WithQueueAwareness
{
    use HttpBasicAuth;

    /**
     * Whether this Queue instance did fire the AJAX action request or not.
     *
     * @var bool
     */
    private $didFireAjaxAction = false;

    /**
     * Returns the Queue default priority that will be used to schedule actions when the
     * priority is not specified or is specified as an invalid value.
     *
     * @return int The Queue default priority.
     */
    public static function getDefaultPriority()
    {
        return 0;
    }

    /**
     * Fires a non-blocking request to the WordPress admin AJAX endpoint that will,
     * in turn, trigger the processing of more Actions.
     *
     * @param mixed|null $bodyData An optional set of data to customize the processing request
     *                             for. If not provided, then the request will be fired for the
     *                             next available Actions (normal operations).
     *
     * @return bool A value that will indicate whether the request was correctly dispatched
     *              or not.
     */
    public function fireAjaxAction($bodyData = null)
    {
        if ($this->didFireAjaxAction) {
            // Let's not fire the AJAX request more than once per HTTP request, per Queue.
            return false;
        }

        $ajaxUrl = add_query_arg([
            'action'      => QueueProcessor::ACTION_QUEUE_PROCESS,
            '_ajax_nonce' => wp_create_nonce(QueueProcessor::ACTION_QUEUE_PROCESS),
        ], admin_url('admin-ajax.php'));

        $useGetMethod = false;
        $requestSent  = false;
        // If we are in a cron job, check if GET/POST method works and set it in a transient for caching
        $useGetMethod = get_site_transient(QueueProcessor::TRANSIENT_REQUEST_GET_METHOD);
        // Transient return false for non existing or expired values, for type safety we will use string 'Yes' or 'No' for GET method usage
        if ($useGetMethod === false) {
            // By default we use POST method, so if that doesn't work we will use GET method
            $useGetMethod = $this->checkGetRequestNeededForQueue($ajaxUrl, $bodyData);
            // We already sent the POST method request. Let not double sent request if we continue use POST method
            $requestSent  = !$useGetMethod;

            set_site_transient(QueueProcessor::TRANSIENT_REQUEST_GET_METHOD, $useGetMethod ? 'Yes' : 'No', HOUR_IN_SECONDS);
            debug_log('[WPSTG Fire Ajax] GET method is ' . ($useGetMethod ? 'needed' : 'not needed') . ' for Queue AJAX request.', 'info', false);
        } else {
            $useGetMethod = $useGetMethod === 'Yes';
        }

        // If request already sent let early bail
        if ($requestSent) {
            $this->didFireAjaxAction = true;

            Hooks::doAction('wpstg_queue_fire_ajax_request', $this);

            return true;
        }

        // If filter is present lets override it!
        $useGetMethod = Hooks::applyFilters(QueueProcessor::FILTER_REQUEST_FORCE_GET_METHOD, $useGetMethod);

        $blocking = $this->useBlockingRequest();
        debug_log('[WPSTG Fire Ajax] Firing AJAX request to process Queue actions. GET method: ' . ($useGetMethod ? 'Yes' : 'No'), 'debug', false);

        $response = wp_remote_request(esc_url_raw($ajaxUrl), [
            'headers'   => array_merge(
                ['X-WPSTG-Request' => QueueProcessor::ACTION_QUEUE_PROCESS],
                $this->getHttpAuthHeaders()
            ),
            'method'    => $useGetMethod ? 'GET' : 'POST',
            'blocking'  => $blocking,
            'timeout'   => $blocking ? 30 : 0.01, // 0.01 for a non-blocking request
            'cookies'   => $this->getLoginRelatedCookies(),
            'sslverify' => apply_filters(FeatureDetection::FILTER_HTTPS_LOCAL_SSL_VERIFY, false),
            'body'      => $this->normalizeAjaxRequestBody($bodyData),
        ]);

        /*
         * A non-blocking request will either return a WP_Error instance, or
         * a mock response. The response is a mock as we cannot really build
         * a good response without waiting for it to be processed from the server.
         */
        if ($response instanceof WP_Error) {
            \WPStaging\functions\debug_log(json_encode([
                'root'     => 'Queue processing admin-ajax request failed.',
                'class'    => get_class($this),
                'code'     => $response->get_error_code(),
                'message'  => $response->get_error_message(),
                'data'     => $response->get_error_data(),
                'blocking' => $blocking,
                'method'   => $useGetMethod ? 'GET' : 'POST',
            ], JSON_PRETTY_PRINT));

            delete_site_transient(QueueProcessor::TRANSIENT_REQUEST_GET_METHOD);
            $this->recordFireFailure();

            return false;
        }

        if ($blocking && is_array($response)) {
            $code = isset($response['response']['code']) ? (int)$response['response']['code'] : 0;
            if ($code < 200 || $code >= 300) {
                $failures = (int)get_site_transient(QueueProcessor::TRANSIENT_FIRE_FAILURE_COUNT) + 1;
                debug_log('[BG Queue] fire failed: HTTP code=' . $code . ' (failure ' . $failures . ')', 'info', false);
                delete_site_transient(QueueProcessor::TRANSIENT_REQUEST_GET_METHOD);
                $this->recordFireFailure();
                return false;
            }

            if ((int)get_site_transient(QueueProcessor::TRANSIENT_FIRE_FAILURE_COUNT) > 0) {
                debug_log('[BG Queue] fire mode -> non-blocking (loopback healthy, code=' . $code . ')', 'info', false);
                delete_site_transient(QueueProcessor::TRANSIENT_FIRE_FAILURE_COUNT);
            }
        }

        // Stamped only after error checks so a failed fire cannot spoof itself as acknowledged.
        set_site_transient(QueueProcessor::TRANSIENT_LAST_FIRE_TIMESTAMP, time(), QueueProcessor::TRANSIENT_FIRE_STATE_TTL);

        $this->didFireAjaxAction = true;

        /**
         * Fires an Action to indicate the Queue did fire the AJAX request that will
         * trigger side-processing in another PHP process.
         *
         * @param Queue $this A reference to the instance of the Queue that actually fired
         *                    the AJAX request.
         */
        do_action('wpstg_queue_fire_ajax_request', $this);

        return true;
    }

    /**
     * Normalizes the data to be sent along the non-blocking AJAX request
     * that will trigger the Queue processing of an Action.
     *
     * @param mixed|null $bodyData The data to normalize to a format suitable for
     *                             the remote request.
     *
     * @return array The normalized body data to be sent along the non-blocking
     *               AJAX request.
     */
    private function normalizeAjaxRequestBody($bodyData)
    {
        $normalized = (array)$bodyData;

        $normalized['_referer'] = __CLASS__;

        return $normalized;
    }

    /**
     * @param string $ajaxUrl
     * @param mixed|null $bodyData
     * @return bool
     */
    private function checkGetRequestNeededForQueue(string $ajaxUrl, $bodyData = null): bool
    {
        // 5s keeps admin UI responsive on broken loopbacks; the stall detector picks up the slack.
        $response = wp_remote_post(esc_url_raw($ajaxUrl), [
            'headers'   => array_merge(
                ['X-WPSTG-Request' => QueueProcessor::ACTION_QUEUE_PROCESS],
                $this->getHttpAuthHeaders()
            ),
            'blocking'  => true,
            'timeout'   => 5,
            'cookies'   => $this->getLoginRelatedCookies(),
            'sslverify' => apply_filters(FeatureDetection::FILTER_HTTPS_LOCAL_SSL_VERIFY, false),
            'body'      => $this->normalizeAjaxRequestBody($bodyData),
        ]);

        if ($response instanceof WP_Error) {
            debug_log('[WPSTG Fire Ajax] checkGetRequestNeededForQueue POST failed: code=' . $response->get_error_code() . ' message=' . $response->get_error_message(), 'debug', false);
        } elseif (is_array($response) && isset($response['response']['code'])) {
            debug_log('[WPSTG Fire Ajax] checkGetRequestNeededForQueue POST response code=' . $response['response']['code'], 'debug', false);
        }

        // If we get WP_Error, then we can assume that POST method doesn't work
        if ($response instanceof WP_Error) {
            return true;
        }

        if (!is_array($response)) {
            return false;
        }

        // If we get 404 response code, then we can assume that POST method doesn't work
        if (
            array_key_exists('response', $response) &&
            array_key_exists('code', $response['response']) &&
            $response['response']['code'] === 404
        ) {
            return true;
        }

        return false;
    }

    private function useBlockingRequest(): bool
    {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return false;
        }

        if (function_exists('wp_get_environment_type') && wp_get_environment_type() === 'local') {
            return true;
        }

        return (int)get_site_transient(QueueProcessor::TRANSIENT_FIRE_FAILURE_COUNT) >= QueueProcessor::ADAPTIVE_BLOCKING_THRESHOLD;
    }

    /**
     * @return void
     */
    private function recordFireFailure()
    {
        $failures = (int)get_site_transient(QueueProcessor::TRANSIENT_FIRE_FAILURE_COUNT);
        if ($failures >= 10) {
            return;
        }

        $newFailures = $failures + 1;
        set_site_transient(QueueProcessor::TRANSIENT_FIRE_FAILURE_COUNT, $newFailures, QueueProcessor::TRANSIENT_FIRE_STATE_TTL);

        if ($failures < QueueProcessor::ADAPTIVE_BLOCKING_THRESHOLD && $newFailures >= QueueProcessor::ADAPTIVE_BLOCKING_THRESHOLD) {
            debug_log('[BG Queue] fire mode -> blocking (consecutive silent failures=' . $newFailures . ')', 'info', false);
        }
    }

    /**
     * Keep only the WordPress login-related cookies to avoid oversized headers.
     * Kept:
     *  - wordpress_[hash]
     *  - wordpress_sec_[hash]
     *  - wordpress_logged_in_[hash]
     *
     * @return array<string,string>
     */
    private function getLoginRelatedCookies(): array
    {
        if (empty($_COOKIE) || !is_array($_COOKIE)) {
            return [];
        }

        $allowed = [];
        foreach ($_COOKIE as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            // Matches: wordpress_[32hex], wordpress_sec_[32hex], wordpress_logged_in_[32hex]
            if (!preg_match('/^wordpress_(?:logged_in_|sec_)?[a-f0-9]{32}$/', $name)) {
                continue;
            }

            if (is_scalar($value)) {
                $allowed[$name] = (string)$value;
            }
        }

        return $allowed;
    }
}
