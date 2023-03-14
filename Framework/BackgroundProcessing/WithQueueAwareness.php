<?php

/**
 * Provides methods to be aware of the queue system and its inner workings.
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */

namespace WPStaging\Framework\BackgroundProcessing;

use WP_Error;

use function WPStaging\functions\debug_log;

/**
 * Trait WithQueueAwareness
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */
trait WithQueueAwareness
{

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
            'action' => QueueProcessor::QUEUE_PROCESS_ACTION,
            '_ajax_nonce' => wp_create_nonce(QueueProcessor::QUEUE_PROCESS_ACTION)
        ], admin_url('admin-ajax.php'));

        $response = wp_remote_post(esc_url_raw($ajaxUrl), [
            'headers' => [
                'X-WPSTG-Request' => QueueProcessor::QUEUE_PROCESS_ACTION,
            ],
            'blocking' => false,
            'timeout' => 0.01,
            'cookies' => isset($_COOKIE) ? $_COOKIE : [],
            'sslverify' => apply_filters('https_local_ssl_verify', false),
            'body' => $this->normalizeAjaxRequestBody($bodyData),
        ]);

        //debug_log('fireAjaxAction: ' . wp_json_encode($response, JSON_PRETTY_PRINT));

        /*
         * A non-blocking request will either return a WP_Error instance, or
         * a mock response. The response is a mock as we cannot really build
         * a good response without waiting for it to be processed from the server.
         */
        if ($response instanceof WP_Error) {
            \WPStaging\functions\debug_log(json_encode([
                'root' => 'Queue processing admin-ajax request failed.',
                'class' => get_class($this),
                'code' => $response->get_error_code(),
                'message' => $response->get_error_message(),
                'data' => $response->get_error_data()
            ], JSON_PRETTY_PRINT));

            return false;
        }

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
     * @param mixed|null $bodyData The data to normlize to a format suitable for
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
}
