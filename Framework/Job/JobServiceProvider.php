<?php

namespace WPStaging\Framework\Job;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\DI\FeatureServiceProvider;
use WPStaging\Framework\Job\Ajax\Cancel;
use WPStaging\Framework\Job\Ajax\PrepareCancel;
use WPStaging\Framework\Job\Ajax\Status;
use WPStaging\Framework\Logger\BackgroundLogger;
use WPStaging\Framework\Logger\SseEventCache;
use WPStaging\Framework\Rest\Rest;
use WPStaging\Framework\Security\Auth;

class JobServiceProvider extends FeatureServiceProvider
{
    protected function registerClasses()
    {
        $this->container->singleton(BackgroundLogger::class);
    }

    protected function addHooks()
    {
        $this->enqueueAjaxListeners();

        // This is needed for PHP 8.4 otherwise wordpress sent header and we cannot change it for event streaming.
        add_filter('rest_pre_dispatch', $this->container->callback(BackgroundLogger::class, 'maybePrepareSseStream'), 10, 3);
        add_action('rest_api_init', [$this, 'registerRestEndpoints']);
        add_action(SseEventCache::ACTION_SSE_CACHE_CLEANUP, [$this, 'sseCacheCleanup']);
    }

    protected function enqueueAjaxListeners()
    {
        add_action('wp_ajax_wpstg--job--status', $this->container->callback(Status::class, 'ajaxProcess')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--job--prepare-cancel', $this->container->callback(PrepareCancel::class, 'ajaxPrepare')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--job--cancel', $this->container->callback(Cancel::class, 'ajaxProcess')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }

    public function registerRestEndpoints()
    {
        register_rest_route(Rest::WPSTG_ROUTE_NAMESPACE_V1, '/ping', [
            'methods'             => 'GET',
            'callback'            => function () {
                wp_send_json_success();
            },
            'permission_callback' => function () {
                /** @var Auth $auth */
                $auth = WPStaging::make(Auth::class);
                if (!$auth->isAuthenticatedRequest()) {
                    return new \WP_Error('rest_forbidden', esc_html__('You are not allowed to access this resource.', 'wp-staging'), ['status' => 403]);
                }

                return true;
            },
        ]);

        register_rest_route(Rest::WPSTG_ROUTE_NAMESPACE_V1, '/sse-logs', [
            'methods'             => 'GET',
            'callback'            => $this->container->callback(BackgroundLogger::class, 'restEventStream'),
            'permission_callback' => $this->container->callback(BackgroundLogger::class, 'verifyRestRequest'),
        ]);
    }

    public function sseCacheCleanup(string $jobId)
    {
        $this->container->get(SseEventCache::class)->delete($jobId);
    }
}
