<?php

namespace WPStaging\Framework;

use WPStaging\Core\Utils\Cache;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\AnalyticsConsent;
use WPStaging\Framework\Analytics\AnalyticsEventDto;
use WPStaging\Framework\Analytics\AnalyticsSender;
use WPStaging\Framework\DI\FeatureServiceProvider;

class AnalyticsServiceProvider extends FeatureServiceProvider
{
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

            $errorMessage = html_entity_decode($_POST['error_message']);
            $errorMessage = sanitize_text_field($errorMessage);

            $jobId = html_entity_decode($_POST['job_id']);
            $jobId = sanitize_text_field($jobId);

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

            $errorMessage = html_entity_decode($_POST['error_message']);
            // prevent emptying HTML string, as Staging errors might be returned in HTML (?)
            $errorMessage = wp_kses_post($errorMessage);

            /**
             * Get the "options" object from cache
             * @see \WPStaging\Backend\Modules\Jobs\Job::__construct
             */
            $cache = new Cache(-1, WPStaging::getContentDir());
            $options = $cache->get("clone_options");

            if (is_object($options) && property_exists($options, 'jobIdentifier')) {
                $jobId = $options->jobIdentifier;
            }

            AnalyticsEventDto::enqueueErrorEvent($jobId, $errorMessage);
        });

        $this->container->make(AnalyticsSender::class)->maybeSend();
    }
}
