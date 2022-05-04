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

    const QUEUE_PROCESS_ACTION = 'wpstg_queue_process';

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
        debug_log('[Background Processing] QueueProcessor::process 1', 'debug');
        if (!$this->doProcess) {
            return 0;
        }

        debug_log('[Background Processing] QueueProcessor::process 2', 'debug');

        $processed = 0;

        /** @var Action|null */
        $previousAction = null;

        while (!$this->isThreshold()) {
            $action = $this->queue->getNextAvailable();

            debug_log('[Background Processing] QueueProcessor::process Action: ' . wp_json_encode($action), 'debug');

            if (!$action instanceof Action) {
                // No READY Actions, no lock or no table.
                debug_log('[Background Processing] QueueProcessor::process No READY actions', 'debug');
                break;
            }

            // Early bail to reset variable stored in memory
            if ($previousAction !== null && $previousAction->jobId !== $action->jobId && $previousAction->action === $action->action) {
                $this->queue->updateActionStatus($action, Queue::STATUS_READY);
                break;
            }

            $processed++;

            $this->dispatch($action);

            debug_log('[Background Processing] QueueProcessor::process After dispatch', 'debug');

            $previousAction = $action;
        }

        /*
         * We will continue processing the Queue if we were able to process
         * at least ONE action. Otherwise, if a MySQL error happens during
         * the processing of the Queue, this would cause a processing loop.
         */
        if ($processed > 0 && $this->queue->count(Queue::STATUS_READY)) {
            // If there are more Actions to process, then keep processing.
            $this->fireAjaxAction();
            debug_log('[Background Processing] QueueProcessor::process After fireAjaxAction', 'debug');
        }

        debug_log('[Background Processing] QueueProcessor::process Processed: ' . $processed, 'debug');

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
            /*
             * This is a PHP 7.0+ interface. But since PHP will NOT check this for existence,
             * the same way it will not check `instanceof`, this allows us to catch fatals
             * and keep going if we're on PHP 7.0+.
             */
            $markFailed();

            return false;
        } catch (\Exception $e) {
            /*
             * If we're not on PHP 7.0+, then this is the next best option: there
             * was an error we could catch, and we did. If the request dies due to
             * a fatal, then we'll handle that in the `shutdown` hook.
             */
            $markFailed();

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
