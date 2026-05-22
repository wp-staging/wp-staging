<?php

/**
 * Processes Actions from the Queue, dispatching the correct Actions and methods with
 * awareness of the available resources.
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */

namespace WPStaging\Framework\BackgroundProcessing;

use ReflectionMethod;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\PhpAdapter;
use WPStaging\Framework\Traits\ResourceTrait;

use function WPStaging\functions\debug_log;

/**
 * Class QueueProcessor
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */
class QueueProcessor
{
    use ResourceTrait;
    use WithQueueAwareness;

    /** @var string */
    const ACTION_QUEUE_PROCESS = 'wpstg_queue_process';

    /** @var string */
    const FILTER_REQUEST_FORCE_GET_METHOD = 'wpstg.queue.request.force_get_method';

    /** @var string */
    const TRANSIENT_REQUEST_GET_METHOD = 'wpstg.queue.request.get_method';

    /** @var string */
    const TRANSIENT_LAST_FIRE_TIMESTAMP = 'wpstg_queue_last_fire_ts';

    /** @var string */
    const TRANSIENT_FIRE_FAILURE_COUNT = 'wpstg_queue_fire_failure_count';

    /** @var int */
    const TRANSIENT_FIRE_STATE_TTL = HOUR_IN_SECONDS;

    /** @var int Consecutive non-acknowledged fires that trigger blocking-mode escalation. */
    const ADAPTIVE_BLOCKING_THRESHOLD = 2;

    /** @var int */
    const FIRE_ACK_WINDOW_SECONDS = 90;

    /**
     * A flag property indicating whether the Queue Processor
     * should actually process Actions or not.
     *
     * @var bool
     */
    private $doProcess = true;

    /**
     * A reference to the Queue instance the Processor should use to access and run low-level
     * operations on the Queue.
     *
     * @var Queue
     */
    private $queue;

    /** @var PhpAdapter */
    private $phpAdapter;

    /** @var int */
    private $inlineRetryDepth = 0;

    /** @var int */
    const INLINE_RETRY_MAX = 1;

    /**
     * QueueProcessor constructor.
     *
     * @param Queue $queue A reference to the Queue instance the Processor should use to access
     *                     and run low-level operations on the Queue.
     * @param PhpAdapter $phpAdapter
     */
    public function __construct(Queue $queue, PhpAdapter $phpAdapter)
    {
        $this->queue      = $queue;
        $this->phpAdapter = $phpAdapter;
    }

    /**
     * Runs the next available Actions until either they are finished or the resources available
     * are depleted.
     *
     * @return int The number of processed Actions. Processed does NOT mean Completed, it just means
     *             the Processor took hold of the Actions and dispatched them where required.
     */
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
            // Silent-drop: a prior fire was never acknowledged inside the window while READY work
            // remains. Escalate so the next non-AJAX fire surfaces the real HTTP response.
            $this->recordFireFailure();
            delete_site_transient(self::TRANSIENT_LAST_FIRE_TIMESTAMP);
        }

        $processed = 0;

        /** @var Action|null */
        $previousAction = null;

        while (!$this->isThreshold()) {
            $action = $this->queue->getNextAvailable();

            if (!$action instanceof Action) {
                // No READY Actions, no lock or no table.
                break;
            }

            // Early bail to reset variable stored in memory
            if ($previousAction !== null && $previousAction->jobId !== $action->jobId && $previousAction->action === $action->action) {
                $this->queue->updateActionStatus($action, Queue::STATUS_READY);
                break;
            }

            $processed++;

            $this->dispatch($action);

            $previousAction = $action;
        }

        /*
         * We will continue processing the Queue if we were able to process
         * at least ONE action. Otherwise, if a MySQL error happens during
         * the processing of the Queue, this would cause a processing loop.
         */
        $fired          = false;
        $remainingReady = (int)$this->queue->count(Queue::STATUS_READY);
        if ($processed > 0 && $remainingReady > 0) {
            $fired = $this->fireAjaxAction();

            // Inline fallback: when the fire fails and we still have resources, drain one more
            // pass in this request rather than waiting for an external trigger that may never come.
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

    /**
     * Depending on the Action nature, dispatch the Action correctly.
     *
     * Note that a successful dispatch means that the execution
     * of the WordPress action, function or method did not raise
     * Exceptions, and not that it did what was required correctly.
     * The task of assessing success, and acting on that information
     * to, maybe, queue the Action again (retry) is a task better performed
     * by the code that is fired, NOT the Queue Processor.
     *
     * @param Action $action A reference to the Action to dispatch.
     *
     * @return bool A value that will indicate whether the Action was
     *              dispatched without raising Exceptions or not.
     *
     * @throws Exceptions\QueueException
     */
    public function dispatch(Action $action)
    {
        debug_log('[BG Queue] dispatch id=' . (int)$action->id . ' job=' . (string)$action->jobId . ' action=' . (string)$action->action, 'info', false);

        /*
         * What is this?
         * It's a Closure that will mark this Action as failed.
         * We hook this Closure on the `shutdown` hook.
         * When the Closure is called, either in the context of the `shutdown` hook,
         * it will unhook itself from the `shutdown` hook to have a call-at-most-once
         * Closure.
         */
        $markFailed = function () use ($action, &$markFailed) {
            remove_action('shutdown', $markFailed);
            $this->queue->updateActionStatus($action, Queue::STATUS_FAILED);
        };

        // If the request dies in the next lines, the Action failed.
        add_action('shutdown', $markFailed);

        $originalUpdateTime = $action->updatedAt;

        try {
            $actionCallback = $action->action;

            if ($this->phpAdapter->isCallable($actionCallback)) {
                // Function, static or instance method.
                if (function_exists($actionCallback)) {
                    // Function, just call it.
                    call_user_func_array($actionCallback, $action->args);
                } else {
                    /*
                     * Static or instance methods: different versions of PHP will mark `Class::method` as
                     * callable even when it's an instance method, so we have to check if it's static or
                     * not.
                     */
                    list($class, $method) = explode('::', $actionCallback, 2);
                    $methodReflection = new ReflectionMethod($class, $method);
                    if ($methodReflection->isStatic()) {
                        // Static method, just call it.
                        call_user_func_array($actionCallback, $action->args);
                    } else {
                        // Instance method: build the instance using the Service Locator, then call the method on it.
                        $instance = WPStaging::make($class);

                        if (method_exists($instance, 'setCurrentAction')) {
                            $instance->setCurrentAction($action);
                        }

                        call_user_func_array([$instance, $method], [$action->args]);
                    }
                }
            } else {
                // If nothing of the above, then treat it as a WP action.
                do_action_ref_array($actionCallback, $action->args);
            }
        } catch (\Throwable $e) {
            debug_log($e->getMessage() . ' ' . $e->getTraceAsString());
            // Only mark as failed if not cancelled (499).
            if ($e->getCode() !== 499) {
                $markFailed();
            }

            return false;
        }

        // All fine, we survived.
        remove_action('shutdown', $markFailed);

        // Re-fetch the Action to check if it was updated during dispatch.
        $latestActionState = $this->queue->getAction($action->id, true);
        if ($latestActionState->status === Queue::STATUS_READY) {
            return true;
        }

        $updatedAt = $latestActionState->updatedAt;
        $updatedDuringDispatch = $originalUpdateTime === $updatedAt;

        if (!$updatedDuringDispatch) {
            // If an Action was updated during dispatch, we'll avoid re-setting its status to another value.
            return true;
        }

        $this->queue->updateActionStatus($action, Queue::STATUS_COMPLETED);

        return true;
    }

    /**
     * Suspends the Queue Processer operations. Any call to the
     * `process` method will be a no-op that will not process and
     * dispatch any action.
     *
     * @since TBD
     *
     * @return bool Whether the Queue Processor was correctly suspended or not.
     */
    public function stopProcessing()
    {
        $this->doProcess = false;
        return true;
    }

    /**
     * Resumes the Queue Processor processing of Actions, if stopped.
     *
     * @since TBD
     *
     * @return bool Whether the Queue Processor did correctly resume or not.
     */
    public function resumeProcessing()
    {
        $this->doProcess = true;
        return true;
    }
}
