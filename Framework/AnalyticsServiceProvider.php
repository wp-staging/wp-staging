<?php

namespace WPStaging\Framework;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\AnalyticsConsent;
use WPStaging\Framework\Analytics\AnalyticsEventDto;
use WPStaging\Framework\Analytics\ErrorCode;
use WPStaging\Framework\Analytics\AnalyticsGenericEventHandler;
use WPStaging\Framework\Analytics\AnalyticsSender;
use WPStaging\Framework\DI\FeatureServiceProvider;
use WPStaging\Framework\Notices\Notices;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Utils\Sanitize;

class AnalyticsServiceProvider extends FeatureServiceProvider
{
 
    private $sanitize;

    public static function getFeatureTrigger()
    {
        return 'WPSTG_FEATURE_ANALYTICS';
    }

    protected function registerClasses()
    {
        $this->container->singleton(AnalyticsConsent::class);
        $this->container->singleton(AnalyticsSender::class);
    }

    protected function addHooks()
    {
        add_action(Notices::ACTION_ADMIN_NOTICES, $this->container->callback(AnalyticsConsent::class, 'maybeShowConsentFailureNotice'));
        add_action('admin_init', $this->container->callback(AnalyticsConsent::class, 'listenForConsent'));

        $this->sanitize = WPStaging::make(Sanitize::class);









        add_action("wp_ajax_wpstg_job_error", function () { // phpcs:ignore WPStaging.Security.AuthorizationChecked
            if (empty($_POST)) {
                return;
            }

            if (!$this->container->make(Auth::class)->isAuthenticatedRequest()) {
                return;
            }

            foreach (['error_message', 'job_id'] as $requiredKeys) {
                if (!isset($_POST[$requiredKeys])) {
                    return;
                }
            }

            $errorMessage = isset($_POST['error_message']) ? $this->sanitize->htmlDecodeAndSanitize($_POST['error_message']) : '';

            $jobId = isset($_POST['job_id']) ? $this->sanitize->htmlDecodeAndSanitize($_POST['job_id']) : '';

            $errorCode = isset($_POST['error_code']) ? ErrorCode::sanitize($this->sanitize->htmlDecodeAndSanitize($_POST['error_code'])) : '';

            AnalyticsEventDto::enqueueErrorEvent($jobId, $errorMessage, $errorCode);
        });

 
        add_action("wp_ajax_wpstg_staging_job_error", function () { // phpcs:ignore WPStaging.Security.AuthorizationChecked
            if (empty($_POST)) {
                return;
            }

            if (!$this->container->make(Auth::class)->isAuthenticatedRequest()) {
                return;
            }

            foreach (['error_message'] as $requiredKeys) {
                if (!isset($_POST[$requiredKeys])) {
                    return;
                }
            }

 
            $errorMessage = isset($_POST['error_message']) ? $this->sanitize->htmlDecodeAndSanitize($_POST['error_message']) : '';






            $cache = WPStaging::make(Cache::class);
            $cache->setLifetime(-1); 
            $cache->setPath(WPStaging::getContentDir());

            $options = $cache->get("clone_options");

            $jobId = '';
            if (is_object($options) && property_exists($options, 'jobIdentifier')) {
                $jobId = $options->jobIdentifier;
            }

            if (empty($jobId)) {
                return;
            }

            $errorCode = isset($_POST['error_code']) ? ErrorCode::sanitize($this->sanitize->htmlDecodeAndSanitize($_POST['error_code'])) : '';

            AnalyticsEventDto::enqueueErrorEvent($jobId, $errorMessage, $errorCode);
        });

        add_action('wp_ajax_wpstg_event_generic', $this->container->callback(AnalyticsGenericEventHandler::class, 'ajaxHandleGenericEvent')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        $this->container->make(AnalyticsSender::class)->maybeSend();
    }
}
