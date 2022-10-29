<?php

/**
 * Manages the registration and hooking of the Background Processing support feature.
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */

namespace WPStaging\Framework\BackgroundProcessing;

use WPStaging\Core\Cron\Cron;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\DI\FeatureServiceProvider;

use function WPStaging\functions\debug_log;

/**
 * Class BackgroundProcessingServiceProvider
 *
 * @property  \tad_DI52_Container container
 * @package WPStaging\Framework\BackgroundProcessing
 */
class BackgroundProcessingServiceProvider extends FeatureServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public static function getFeatureTrigger()
    {
        return 'WPSTG_FEATURE_ENABLE_BACKGROUND_PROCESSING';
    }

    /**
     * Registers the required Cron actions and the classes used by the feature provider.
     *
     * @return bool Whether the feature registration was actually done or not.
     */
    public function register()
    {
        // This allows us to disable or enable the feature by setting WPSTG_FEATURE_ENABLE_BACKGROUND_PROCESSING to false/true in wp-config.php
        if (!static::isEnabledInProduction()) {
            return false;
        }

        $database = $this->container->make(Database::class)->getClient();

        // See if there is better way than this to handle this code?
        $this->container->when(Queue::class)
            ->needs(InterfaceDatabaseClient::class)
            ->give($database);

        // For caching purposes, have one single instance of the Queue around.
        $this->container->singleton(Queue::class, Queue::class);
        // For concurrency purposes, have one single instance of the Queue processor around.
        $this->container->singleton(QueueProcessor::class, QueueProcessor::class);

        $this->registerFeatureDetection();
        $this->scheduleQueueMaintenance();
        $this->setupQueueProcessingEntrypoints();

        return true;
    }

    /**
     * Runs the Queue maintenance routines.
     *
     * @return void The method will not return any value.
     */
    public function runQueueMaintenance()
    {
        debug_log('Running Queue Maintenance.');

        /** @var Queue $queue */
        $queue = $this->container->make(Queue::class);

        // Mark all dangling Actions as Failed.
        $queue->markDanglingAs(Queue::STATUS_FAILED);
        // Remove old Actions.
        $queue->cleanup();
    }

    /**
     * Schedules the Queue maintenance by means of the Cron. The Cron is not
     * a really reliable method to execute timely tasks in WordPress, especially
     * if not powered by a real cron, but it's fine for addressing the maintenance
     * operations of the Queue that do not require to be timely and are fine happening
     * when possible.
     *
     * @since TBD
     *
     */
    private function scheduleQueueMaintenance()
    {
        // Once a day fire an action to run the Queue maintenance routines.
        if (!wp_next_scheduled('wpstg_queue_maintain')) {
            wp_schedule_event(time(), Cron::DAILY, 'wpstg_queue_maintain');
        }

        // When the action fires, run the maintenance routines.
        add_action('wpstg_queue_maintain', [$this, 'runQueueMaintenance']);
    }

    /**
     * Sets up the Queue processing entry points.
     *
     * The Queue, when loaded with Actions, has the potential to soak resources.
     * The Queue Processor will have safeguards in place to avoid this, but we should
     * be careful about the entrypoints of the queue processing to make sure it will
     * process Actions only when doing that will not compromise the user experience.
     * This is why we rely on side-processes that we can trigger while the main PHP process
     * that is handling the user interaction with the site stays fast and snappy.
     *
     * @return void The method does not return any value.
     */
    private function setupQueueProcessingEntrypoints()
    {
        /**
         * This is the core of how the Queue works: when the `wpstg_queue_process`, or the AJAX version of it, fires, we'll process some
         * Actions.
         * Setting up how we make these WordPress actions fire is what we take care of next.
         */
        $wpActions = [
            QueueProcessor::QUEUE_PROCESS_ACTION,
            'wp_ajax_nopriv_' . QueueProcessor::QUEUE_PROCESS_ACTION,
            'wp_ajax_' . QueueProcessor::QUEUE_PROCESS_ACTION,
        ];
        $queueProcessorProcess = $this->container->callback(QueueProcessor::class, 'process');

        foreach ($wpActions as $wpAction) {
            if (!has_action($wpAction, $queueProcessorProcess)) {
                add_action($wpAction, $queueProcessorProcess);
            }
        }

        /*
         * The first way we trigger the action that will make the Queue Processor process Actions is a Cron schedule.
         * With full-knowledge of the fact that it will not be reliable, we still try to get some work done
         * on Cron calls.
         * Once every hour (kinda, it's Cron), fire the `wpstg_queue_process` action.
         */
        if (!wp_next_scheduled('wpstg_queue_process')) {
            wp_schedule_event(time(), Cron::HOURLY, QueueProcessor::QUEUE_PROCESS_ACTION);
        }

        /*
         * This is currently deactivated while we decide if supporting this is something we would like to do at all.
        if (is_admin() && !wp_doing_ajax()) {
            $ajaxAvailable = $this->container->make(FeatureDetection::class)->isAjaxAvailable(false);

            if (!$ajaxAvailable) {
                // add_action('shutdown', $queueProcessorProcess, -10000);
            }
        }
        */
    }

    /**
     * Registers the two actions that will be called by the AJAX support
     * feature detection.
     *
     * @since TBD
     */
    private function registerFeatureDetection()
    {
        // Register the method that will handle the AJAX check.
        $updateOption = $this->container->callback(FeatureDetection::class, 'updateAjaxTestOption');
        // Hook on authenticated AJAX endpoint to handle the check.
        add_action('wp_ajax_' . FeatureDetection::AJAX_TEST_ACTION, $updateOption);
        add_action('wp_ajax_nopriv_' . FeatureDetection::AJAX_TEST_ACTION, $updateOption);

        // Once a week re-run the check.
        if (!wp_next_scheduled('wpstg_q_ajax_support_feature_detection')) {
            wp_schedule_event(time(), Cron::WEEKLY, 'wpstg_q_ajax_support_feature_detection');
        }

        $runAjaxFeatureTest = $this->container->callback(FeatureDetection::class, 'runAjaxFeatureTest');
        add_action('wpstg_q_ajax_support_feature_detection', $runAjaxFeatureTest);

        // Run the test again if requested by link, e.g. from the notice.
        if (
            is_admin()
            && filter_input(INPUT_GET, FeatureDetection::AJAX_REQUEST_QUERY_VAR, FILTER_SANITIZE_NUMBER_INT)
        ) {
            $runAjaxFeatureTest();
            wp_redirect(remove_query_arg(FeatureDetection::AJAX_REQUEST_QUERY_VAR));
            die();
        }
    }
}
