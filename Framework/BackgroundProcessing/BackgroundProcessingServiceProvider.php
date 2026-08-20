<?php










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
 
    const ACTION_QUEUE_MAINTAIN = 'wpstg_queue_maintain';

 
    const TRANSIENT_STALL_PROBE_LOCK = 'wpstg_queue_stall_probe_lock';

 
    const TRANSIENT_QUEUE_HAS_WORK = 'wpstg_queue_has_work';

 
    const QUEUE_HAS_WORK_TTL = DAY_IN_SECONDS;

 
    const STALL_PROBE_THROTTLE_SECONDS = 15;

 
    const STALL_IDLE_SECONDS = 20;

 
    const STUCK_PROCESSING_SECONDS = 60;




    public static function getFeatureTrigger()
    {
        return 'WPSTG_FEATURE_ENABLE_BACKGROUND_PROCESSING';
    }






    public function register()
    {
 
        if (!static::isEnabledInProduction()) {
            return false;
        }

        $database = $this->container->make(Database::class)->getClient();

 
        $this->container->when(Queue::class)
            ->needs(InterfaceDatabaseClient::class)
            ->give($database);

 
        $this->container->singleton(Queue::class, Queue::class);
 
        $this->container->singleton(QueueProcessor::class, QueueProcessor::class);

        $this->registerFeatureDetection();
        $this->scheduleQueueMaintenance();
        $this->setupQueueProcessingEntrypoints();
        $this->setupStallDetector();

 
        if (did_action('init')) {
            $this->scheduleStaticCronEvents();
        } else {
            add_action('init', [$this, 'scheduleStaticCronEvents']);
        }

        return true;
    }






    public function runQueueMaintenance()
    {
        debug_log('Running Queue Maintenance.', 'info', false);

 
        $queue = $this->container->make(Queue::class);

 
        $queue->markDanglingAs(Queue::STATUS_FAILED);
 
        $queue->cleanup();
    }





    public function scheduleStaticCronEvents()
    {
 
        $cron = $this->container->make(Cron::class);

 
        if (!wp_next_scheduled(self::ACTION_QUEUE_MAINTAIN)) {
            wp_schedule_event($cron->getFirstRunTimestamp(Cron::DAILY), Cron::DAILY, self::ACTION_QUEUE_MAINTAIN);
        }

 
        if (!wp_next_scheduled(QueueProcessor::ACTION_QUEUE_PROCESS)) {
            wp_schedule_event($cron->getFirstRunTimestamp(Cron::HOURLY), Cron::HOURLY, QueueProcessor::ACTION_QUEUE_PROCESS);
        }

 
        if (!wp_next_scheduled(FeatureDetection::ACTION_AJAX_SUPPORT_FEATURE_DETECTION)) {
            wp_schedule_event($cron->getFirstRunTimestamp(Cron::WEEKLY), Cron::WEEKLY, FeatureDetection::ACTION_AJAX_SUPPORT_FEATURE_DETECTION);
        }
    }












    private function scheduleQueueMaintenance()
    {
 
        add_action(self::ACTION_QUEUE_MAINTAIN, [$this, 'runQueueMaintenance']); // phpcs:ignore WPStaging.Security.FirstArgNotAString
    }













    private function setupQueueProcessingEntrypoints()
    {





        $wpActions = [
            QueueProcessor::ACTION_QUEUE_PROCESS,
            'wp_ajax_nopriv_' . QueueProcessor::ACTION_QUEUE_PROCESS,
            'wp_ajax_' . QueueProcessor::ACTION_QUEUE_PROCESS,
        ];
        $queueProcessorProcess = $this->container->callback(QueueProcessor::class, 'process');

        foreach ($wpActions as $wpAction) {
            if (!has_action($wpAction, $queueProcessorProcess)) {
                add_action($wpAction, $queueProcessorProcess); // phpcs:ignore WPStaging.Security.FirstArgNotAString -- Queue action callbacks should not take input from request.
            }
        }











    }





    private function setupStallDetector()
    {
        add_action('init', [$this, 'detectAndRecoverStall'], 100);
    }




    public function detectAndRecoverStall()
    {
        if (!get_site_transient(self::TRANSIENT_QUEUE_HAS_WORK)) {
            return;
        }

        if (function_exists('wp_doing_cron') && wp_doing_cron()) {
            return;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $requestAction = isset($_REQUEST['action']) ? sanitize_text_field(wp_unslash($_REQUEST['action'])) : '';
            if ($requestAction === QueueProcessor::ACTION_QUEUE_PROCESS || $requestAction === FeatureDetection::ACTION_AJAX_TEST) {
                return;
            }
        }

        if (get_site_transient(self::TRANSIENT_STALL_PROBE_LOCK)) {
            return;
        }

        set_site_transient(self::TRANSIENT_STALL_PROBE_LOCK, 1, self::STALL_PROBE_THROTTLE_SECONDS);

        try {
 
            $queue = $this->container->make(Queue::class);
        } catch (\Throwable $e) {
            return;
        }

        $revived = 0;
        try {
            $breakpoint = $this->getStuckProcessingBreakpoint();
            if ($breakpoint !== null) {
                $revived = (int)$queue->markDanglingAs(Queue::STATUS_READY, $breakpoint, true);
                if ($revived > 0) {
                    debug_log('[Background Processing] Revived ' . $revived . ' stuck-in-processing action(s). Claim age threshold: ' . self::STUCK_PROCESSING_SECONDS . 's.', 'info', true);
                }
            }
        } catch (\Throwable $e) {
 
        }

        if ((int)$queue->count(Queue::STATUS_READY) === 0) {
            if ((int)$queue->count(Queue::STATUS_PROCESSING) === 0) {
                delete_site_transient(self::TRANSIENT_QUEUE_HAS_WORK);
            }

            return;
        }

        if ($revived > 0) {
            $this->recoverStalledQueue($queue, 0, $revived);
            return;
        }

        $lastUpdate = $queue->getLastUpdatedAtTimestamp();
        if ($lastUpdate === 0) {
 
            $this->recoverStalledQueue($queue, 0, 0);
            return;
        }

        $idleSeconds = time() - $lastUpdate;
        if ($idleSeconds < self::STALL_IDLE_SECONDS) {
            return;
        }

        $this->recoverStalledQueue($queue, $idleSeconds, 0);
    }




    private function recoverStalledQueue(Queue $queue, $idleSeconds, $revivedCount)
    {
        debug_log('[Background Processing] Stall detected: ready=' . $queue->count(Queue::STATUS_READY) . ' idle_seconds=' . (int)$idleSeconds . ' revived=' . (int)$revivedCount . '. Recovering via inline process().', 'info', true);

        try {
 
            $processor = $this->container->make(QueueProcessor::class);
        } catch (\Throwable $e) {
            return;
        }

        $processor->process();
    }




    private function getStuckProcessingBreakpoint()
    {
        try {
            $breakpoint = new \DateTimeImmutable(current_time('mysql'));
            return $breakpoint->setTimestamp($breakpoint->getTimestamp() - self::STUCK_PROCESSING_SECONDS);
        } catch (\Exception $e) {
            return null;
        }
    }








    private function registerFeatureDetection()
    {
 
        $updateOption = $this->container->callback(FeatureDetection::class, 'updateAjaxTestOption');
 
        add_action('wp_ajax_' . FeatureDetection::ACTION_AJAX_TEST, $updateOption); // phpcs:ignore WPStaging.Security.AuthorizationChecked -- Public
        add_action('wp_ajax_nopriv_' . FeatureDetection::ACTION_AJAX_TEST, $updateOption); // phpcs:ignore WPStaging.Security.AuthorizationChecked -- Public

        $runAjaxFeatureTest = $this->container->callback(FeatureDetection::class, 'runAjaxFeatureTest');
        add_action(FeatureDetection::ACTION_AJAX_SUPPORT_FEATURE_DETECTION, $runAjaxFeatureTest);

 
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
