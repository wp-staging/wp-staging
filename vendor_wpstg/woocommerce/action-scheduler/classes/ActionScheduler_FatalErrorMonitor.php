<?php

namespace WPStaging\Vendor;

/**
 * Class ActionScheduler_FatalErrorMonitor
 */
class ActionScheduler_FatalErrorMonitor
{
    /** @var ActionScheduler_ActionClaim */
    private $claim = \WPStaging\Vendor\NULL;
    /** @var ActionScheduler_Store */
    private $store = \WPStaging\Vendor\NULL;
    private $action_id = 0;
    public function __construct(\WPStaging\Vendor\ActionScheduler_Store $store)
    {
        $this->store = $store;
    }
    public function attach(\WPStaging\Vendor\ActionScheduler_ActionClaim $claim)
    {
        $this->claim = $claim;
        \WPStaging\Vendor\add_action('shutdown', array($this, 'handle_unexpected_shutdown'));
        \WPStaging\Vendor\add_action('action_scheduler_before_execute', array($this, 'track_current_action'), 0, 1);
        \WPStaging\Vendor\add_action('action_scheduler_after_execute', array($this, 'untrack_action'), 0, 0);
        \WPStaging\Vendor\add_action('action_scheduler_execution_ignored', array($this, 'untrack_action'), 0, 0);
        \WPStaging\Vendor\add_action('action_scheduler_failed_execution', array($this, 'untrack_action'), 0, 0);
    }
    public function detach()
    {
        $this->claim = \WPStaging\Vendor\NULL;
        $this->untrack_action();
        \WPStaging\Vendor\remove_action('shutdown', array($this, 'handle_unexpected_shutdown'));
        \WPStaging\Vendor\remove_action('action_scheduler_before_execute', array($this, 'track_current_action'), 0);
        \WPStaging\Vendor\remove_action('action_scheduler_after_execute', array($this, 'untrack_action'), 0);
        \WPStaging\Vendor\remove_action('action_scheduler_execution_ignored', array($this, 'untrack_action'), 0);
        \WPStaging\Vendor\remove_action('action_scheduler_failed_execution', array($this, 'untrack_action'), 0);
    }
    public function track_current_action($action_id)
    {
        $this->action_id = $action_id;
    }
    public function untrack_action()
    {
        $this->action_id = 0;
    }
    public function handle_unexpected_shutdown()
    {
        if ($error = \error_get_last()) {
            if (\in_array($error['type'], array(\E_ERROR, \E_PARSE, \E_COMPILE_ERROR, \E_USER_ERROR, \E_RECOVERABLE_ERROR))) {
                if (!empty($this->action_id)) {
                    $this->store->mark_failure($this->action_id);
                    \WPStaging\Vendor\do_action('action_scheduler_unexpected_shutdown', $this->action_id, $error);
                }
            }
            $this->store->release_claim($this->claim);
        }
    }
}
