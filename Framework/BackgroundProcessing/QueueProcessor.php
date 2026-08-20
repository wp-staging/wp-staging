<?php








namespace WPStaging\Framework\BackgroundProcessing;

use ReflectionMethod;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\PhpAdapter;
use WPStaging\Framework\Traits\ResourceTrait;

use function WPStaging\functions\debug_log;






class QueueProcessor
{
    use ResourceTrait;
    use WithQueueAwareness;

 
    const ACTION_QUEUE_PROCESS = 'wpstg_queue_process';

 
    const FILTER_REQUEST_FORCE_GET_METHOD = 'wpstg.queue.request.force_get_method';

 
    const TRANSIENT_REQUEST_GET_METHOD = 'wpstg.queue.request.get_method';

 
    const TRANSIENT_LAST_FIRE_TIMESTAMP = 'wpstg_queue_last_fire_ts';

 
    const TRANSIENT_FIRE_FAILURE_COUNT = 'wpstg_queue_fire_failure_count';

 
    const TRANSIENT_FIRE_STATE_TTL = HOUR_IN_SECONDS;

 
    const ADAPTIVE_BLOCKING_THRESHOLD = 2;

 
    const FIRE_ACK_WINDOW_SECONDS = 90;







    private $doProcess = true;







    private $queue;

 
    private $phpAdapter;

 
    private $inlineRetryDepth = 0;

 
    const INLINE_RETRY_MAX = 1;








    public function __construct(Queue $queue, PhpAdapter $phpAdapter)
    {
        $this->queue      = $queue;
        $this->phpAdapter = $phpAdapter;
    }








    public function process()
    {
        $lastFireTs  = (int)get_site_transient(self::TRANSIENT_LAST_FIRE_TIMESTAMP);
        $lastFireAge = $lastFireTs > 0 ? (time() - $lastFireTs) : -1;

        if (!$this->doProcess) {
            return 0;
        }

        if ($lastFireTs > 0 && $lastFireAge >= 0 && $lastFireAge <= self::FIRE_ACK_WINDOW_SECONDS) {
            if ((int)get_site_transient(self::TRANSIENT_FIRE_FAILURE_COUNT) !== 0) {
                delete_site_transient(self::TRANSIENT_FIRE_FAILURE_COUNT);
            }
        } elseif ($lastFireTs > 0 && $lastFireAge > self::FIRE_ACK_WINDOW_SECONDS && (int)$this->queue->count(Queue::STATUS_READY) > 0) {
 
 
            $this->recordFireFailure();
            delete_site_transient(self::TRANSIENT_LAST_FIRE_TIMESTAMP);
        }

        $processed = 0;

 
        $previousAction = null;

        while (!$this->isThreshold()) {
            $action = $this->queue->getNextAvailable();

            if (!$action instanceof Action) {
 
                break;
            }

 
            if ($previousAction !== null && $previousAction->jobId !== $action->jobId && $previousAction->action === $action->action) {
                $this->queue->updateActionStatus($action, Queue::STATUS_READY);
                break;
            }

            $processed++;

            $this->dispatch($action);

            $previousAction = $action;
        }






        $fired          = false;
        $remainingReady = (int)$this->queue->count(Queue::STATUS_READY);
        if ($processed > 0 && $remainingReady > 0) {
            $fired = $this->fireAjaxAction();

 
 
            if (!$fired && !$this->isThreshold() && $this->inlineRetryDepth < self::INLINE_RETRY_MAX) {
                $this->inlineRetryDepth++;
                $this->didFireAjaxAction = false;
                debug_log('[BG Queue] inline retry (depth=' . $this->inlineRetryDepth . ')', 'info', false);
                $processed += $this->process();
            }
        }

        if ($this->inlineRetryDepth === 0 && $processed > 0) {
            debug_log('[BG Queue] process done: dispatched=' . $processed . ' remaining=' . $remainingReady . ' fired=' . ($fired ? 'yes' : 'no'), 'info', false);
        }

        if ($this->inlineRetryDepth > 0) {
            $this->inlineRetryDepth--;
        }

        debug_log('[Background Processing] QueueProcessor::process Processed: ' . $processed, 'debug', false);

        return $processed;
    }


















    public function dispatch(Action $action)
    {
        debug_log('[BG Queue] dispatch id=' . (int)$action->id . ' job=' . (string)$action->jobId . ' action=' . (string)$action->action, 'info', false);









        $markFailed = function () use ($action, &$markFailed) {
            remove_action('shutdown', $markFailed);
            $this->queue->updateActionStatus($action, Queue::STATUS_FAILED);
        };

 
        add_action('shutdown', $markFailed);

        $originalUpdateTime = $action->updatedAt;

        try {
            $actionCallback = $action->action;

            if ($this->phpAdapter->isCallable($actionCallback)) {
 
                if (function_exists($actionCallback)) {
 
                    call_user_func_array($actionCallback, $action->args);
                } else {





                    list($class, $method) = explode('::', $actionCallback, 2);
                    $methodReflection = new ReflectionMethod($class, $method);
                    if ($methodReflection->isStatic()) {
 
                        call_user_func_array($actionCallback, $action->args);
                    } else {
 
                        $instance = WPStaging::make($class);

                        if (method_exists($instance, 'setCurrentAction')) {
                            $instance->setCurrentAction($action);
                        }

                        call_user_func_array([$instance, $method], [$action->args]);
                    }
                }
            } else {
 
                do_action_ref_array($actionCallback, $action->args);
            }
        } catch (\Throwable $e) {
            debug_log($e->getMessage() . ' ' . $e->getTraceAsString());
 
            if ($e->getCode() !== 499) {
                $markFailed();
            }

            return false;
        }

 
        remove_action('shutdown', $markFailed);

 
        $latestActionState = $this->queue->getAction($action->id, true);
        if ($latestActionState->status === Queue::STATUS_READY) {
            return true;
        }

        $updatedAt = $latestActionState->updatedAt;
        $updatedDuringDispatch = $originalUpdateTime === $updatedAt;

        if (!$updatedDuringDispatch) {
 
            return true;
        }

        $this->queue->updateActionStatus($action, Queue::STATUS_COMPLETED);

        return true;
    }










    public function stopProcessing()
    {
        $this->doProcess = false;
        return true;
    }








    public function resumeProcessing()
    {
        $this->doProcess = true;
        return true;
    }
}
