<?php







namespace WPStaging\Framework\BackgroundProcessing;

use WP_Error;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Network\HttpBasicAuth;

use function WPStaging\functions\debug_log;






trait WithQueueAwareness
{
    use HttpBasicAuth;






    private $didFireAjaxAction = false;







    public static function getDefaultPriority()
    {
        return 0;
    }












    public function fireAjaxAction($bodyData = null)
    {
        if ($this->didFireAjaxAction) {
 
            return false;
        }

        $ajaxUrl = add_query_arg([
            'action'      => QueueProcessor::ACTION_QUEUE_PROCESS,
            '_ajax_nonce' => wp_create_nonce(QueueProcessor::ACTION_QUEUE_PROCESS),
        ], admin_url('admin-ajax.php'));

        $useGetMethod = false;
        $requestSent  = false;
 
        $useGetMethod = get_site_transient(QueueProcessor::TRANSIENT_REQUEST_GET_METHOD);
 
        if ($useGetMethod === false) {
 
            $useGetMethod = $this->checkGetRequestNeededForQueue($ajaxUrl, $bodyData);
 
            $requestSent  = !$useGetMethod;

            set_site_transient(QueueProcessor::TRANSIENT_REQUEST_GET_METHOD, $useGetMethod ? 'Yes' : 'No', HOUR_IN_SECONDS);
            debug_log('[WPSTG Fire Ajax] GET method is ' . ($useGetMethod ? 'needed' : 'not needed') . ' for Queue AJAX request.', 'info', false);
        } else {
            $useGetMethod = $useGetMethod === 'Yes';
        }

 
        if ($requestSent) {
            $this->didFireAjaxAction = true;

            Hooks::doAction('wpstg_queue_fire_ajax_request', $this);

            return true;
        }

 
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
            'timeout'   => $blocking ? 30 : 0.01, 
            'cookies'   => $this->getLoginRelatedCookies(),
            'sslverify' => apply_filters(FeatureDetection::FILTER_HTTPS_LOCAL_SSL_VERIFY, false),
            'body'      => $this->normalizeAjaxRequestBody($bodyData),
        ]);






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

 
        set_site_transient(QueueProcessor::TRANSIENT_LAST_FIRE_TIMESTAMP, time(), QueueProcessor::TRANSIENT_FIRE_STATE_TTL);

        $this->didFireAjaxAction = true;








        do_action('wpstg_queue_fire_ajax_request', $this);

        return true;
    }











    private function normalizeAjaxRequestBody($bodyData)
    {
        $normalized = (array)$bodyData;

        $normalized['_referer'] = __CLASS__;

        return $normalized;
    }






    private function checkGetRequestNeededForQueue(string $ajaxUrl, $bodyData = null): bool
    {
 
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

 
        if ($response instanceof WP_Error) {
            return true;
        }

        if (!is_array($response)) {
            return false;
        }

 
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
