<?php

namespace WPStaging\Framework;

use WPStaging\Core\Utils\Cache;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\AnalyticsConsent;
use WPStaging\Framework\Analytics\AnalyticsEventDto;
use WPStaging\Framework\Analytics\AnalyticsSender;
use WPStaging\Framework\DI\FeatureServiceProvider;
use WPStaging\Framework\Utils\Sanitize;

class AnalyticsServiceProvider extends FeatureServiceProvider
{
    /** @var Sanitize */
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
        add_action('admin_notices', $this->container->callback(AnalyticsConsent::class, 'maybeShowConsentNotice'));
        add_action('admin_notices', $this->container->callback(AnalyticsConsent::class, 'maybeShowConsentFailureNotice'));
        add_action('admin_init', $this->container->callback(AnalyticsConsent::class, 'listenForConsent'));

        $this->sanitize = WPStaging::make(Sanitize::class);

        /*
         * Analytics error detection for Backup actions
         *
         * The AJAX event name avoids using "analytics_error" on purpose to
         * avoid ad blocks from blocking the request from happening.
         *
         * "analytics" should never be mentioned in JavaScript, only on server-side.
         */
        add_action("wp_ajax_wpstg_job_error", function () {
            if (empty($_POST)) {
                return;
            }

            foreach (['error_message', 'job_id'] as $requiredKeys) {
                if (!isset($_POST[$requiredKeys])) {
                    return;
                }
            }

            $errorMessage = isset($_POST['error_message']) ? $this->sanitize->htmlDecodeAndSanitize($_POST['error_message']) : '';

            $jobId = isset($_POST['job_id']) ? $this->sanitize->htmlDecodeAndSanitize($_POST['job_id']) : '';

            AnalyticsEventDto::enqueueErrorEvent($jobId, $errorMessage);
        });

        // Analytics error detection for Staging actions
        add_action("wp_ajax_wpstg_staging_job_error", function () {
            if (empty($_POST)) {
                return;
            }

            foreach (['error_message'] as $requiredKeys) {
                if (!isset($_POST[$requiredKeys])) {
                    return;
                }
            }

            // prevent emptying HTML string, as Staging errors might be returned in HTML (?)
            $errorMessage = isset($_POST['error_message']) ? $this->sanitize->htmlDecodeAndSanitize($_POST['error_message']) : '';

            /**
             * Get the "options" object from cache
             * @see \WPStaging\Backend\Modules\Jobs\Job::__construct
             */
            $cache = new Cache(-1, WPStaging::getContentDir());
            $options = $cache->get("clone_options");

            if (is_object($options) && property_exists($options, 'jobIdentifier')) {
                $jobId = $options->jobIdentifier;
            }
            if (empty($jobId)) {
                return;
            }

            AnalyticsEventDto::enqueueErrorEvent($jobId, $errorMessage);
        });

        $this->container->make(AnalyticsSender::class)->maybeSend();
    }
}
