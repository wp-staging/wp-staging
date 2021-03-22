<?php

namespace WPStaging\Vendor;

/**
 * Class ActionScheduler_Store
 * @codeCoverageIgnore
 */
abstract class ActionScheduler_Store extends \WPStaging\Vendor\ActionScheduler_Store_Deprecated
{
    const STATUS_COMPLETE = 'complete';
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'in-progress';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELED = 'canceled';
    const DEFAULT_CLASS = 'ActionScheduler_wpPostStore';
    /** @var ActionScheduler_Store */
    private static $store = \WPStaging\Vendor\NULL;
    /** @var int */
    protected static $max_args_length = 191;
    /**
     * @param ActionScheduler_Action $action
     * @param DateTime $scheduled_date Optional Date of the first instance
     *        to store. Otherwise uses the first date of the action's
     *        schedule.
     *
     * @return int The action ID
     */
    public abstract function save_action(\WPStaging\Vendor\ActionScheduler_Action $action, \DateTime $scheduled_date = \WPStaging\Vendor\NULL);
    /**
     * @param string $action_id
     *
     * @return ActionScheduler_Action
     */
    public abstract function fetch_action($action_id);
    /**
     * @param string $hook Hook name/slug.
     * @param array  $params Hook arguments.
     * @return string ID of the next action matching the criteria.
     */
    public abstract function find_action($hook, $params = array());
    /**
     * @param array  $query Query parameters.
     * @param string $query_type Whether to select or count the results. Default, select.
     *
     * @return array|int The IDs of or count of actions matching the query.
     */
    public abstract function query_actions($query = array(), $query_type = 'select');
    /**
     * Get a count of all actions in the store, grouped by status
     *
     * @return array
     */
    public abstract function action_counts();
    /**
     * @param string $action_id
     */
    public abstract function cancel_action($action_id);
    /**
     * @param string $action_id
     */
    public abstract function delete_action($action_id);
    /**
     * @param string $action_id
     *
     * @return DateTime The date the action is schedule to run, or the date that it ran.
     */
    public abstract function get_date($action_id);
    /**
     * @param int      $max_actions
     * @param DateTime $before_date Claim only actions schedule before the given date. Defaults to now.
     * @param array    $hooks       Claim only actions with a hook or hooks.
     * @param string   $group       Claim only actions in the given group.
     *
     * @return ActionScheduler_ActionClaim
     */
    public abstract function stake_claim($max_actions = 10, \DateTime $before_date = null, $hooks = array(), $group = '');
    /**
     * @return int
     */
    public abstract function get_claim_count();
    /**
     * @param ActionScheduler_ActionClaim $claim
     */
    public abstract function release_claim(\WPStaging\Vendor\ActionScheduler_ActionClaim $claim);
    /**
     * @param string $action_id
     */
    public abstract function unclaim_action($action_id);
    /**
     * @param string $action_id
     */
    public abstract function mark_failure($action_id);
    /**
     * @param string $action_id
     */
    public abstract function log_execution($action_id);
    /**
     * @param string $action_id
     */
    public abstract function mark_complete($action_id);
    /**
     * @param string $action_id
     *
     * @return string
     */
    public abstract function get_status($action_id);
    /**
     * @param string $action_id
     * @return mixed
     */
    public abstract function get_claim_id($action_id);
    /**
     * @param string $claim_id
     * @return array
     */
    public abstract function find_actions_by_claim_id($claim_id);
    /**
     * @param string $comparison_operator
     * @return string
     */
    protected function validate_sql_comparator($comparison_operator)
    {
        if (\in_array($comparison_operator, array('!=', '>', '>=', '<', '<=', '='))) {
            return $comparison_operator;
        }
        return '=';
    }
    /**
     * Get the time MySQL formated date/time string for an action's (next) scheduled date.
     *
     * @param ActionScheduler_Action $action
     * @param DateTime $scheduled_date (optional)
     * @return string
     */
    protected function get_scheduled_date_string(\WPStaging\Vendor\ActionScheduler_Action $action, \DateTime $scheduled_date = \WPStaging\Vendor\NULL)
    {
        $next = null === $scheduled_date ? $action->get_schedule()->get_date() : $scheduled_date;
        if (!$next) {
            return '0000-00-00 00:00:00';
        }
        $next->setTimezone(new \DateTimeZone('UTC'));
        return $next->format('Y-m-d H:i:s');
    }
    /**
     * Get the time MySQL formated date/time string for an action's (next) scheduled date.
     *
     * @param ActionScheduler_Action $action
     * @param DateTime $scheduled_date (optional)
     * @return string
     */
    protected function get_scheduled_date_string_local(\WPStaging\Vendor\ActionScheduler_Action $action, \DateTime $scheduled_date = \WPStaging\Vendor\NULL)
    {
        $next = null === $scheduled_date ? $action->get_schedule()->get_date() : $scheduled_date;
        if (!$next) {
            return '0000-00-00 00:00:00';
        }
        \WPStaging\Vendor\ActionScheduler_TimezoneHelper::set_local_timezone($next);
        return $next->format('Y-m-d H:i:s');
    }
    /**
     * Validate that we could decode action arguments.
     *
     * @param mixed $args      The decoded arguments.
     * @param int   $action_id The action ID.
     *
     * @throws ActionScheduler_InvalidActionException When the decoded arguments are invalid.
     */
    protected function validate_args($args, $action_id)
    {
        // Ensure we have an array of args.
        if (!\is_array($args)) {
            throw \WPStaging\Vendor\ActionScheduler_InvalidActionException::from_decoding_args($action_id);
        }
        // Validate JSON decoding if possible.
        if (\function_exists('json_last_error') && \JSON_ERROR_NONE !== \json_last_error()) {
            throw \WPStaging\Vendor\ActionScheduler_InvalidActionException::from_decoding_args($action_id, $args);
        }
    }
    /**
     * Validate a ActionScheduler_Schedule object.
     *
     * @param mixed $schedule  The unserialized ActionScheduler_Schedule object.
     * @param int   $action_id The action ID.
     *
     * @throws ActionScheduler_InvalidActionException When the schedule is invalid.
     */
    protected function validate_schedule($schedule, $action_id)
    {
        if (empty($schedule) || !\is_a($schedule, 'WPStaging\\Vendor\\ActionScheduler_Schedule')) {
            throw \WPStaging\Vendor\ActionScheduler_InvalidActionException::from_schedule($action_id, $schedule);
        }
    }
    /**
     * InnoDB indexes have a maximum size of 767 bytes by default, which is only 191 characters with utf8mb4.
     *
     * Previously, AS wasn't concerned about args length, as we used the (unindex) post_content column. However,
     * with custom tables, we use an indexed VARCHAR column instead.
     *
     * @param  ActionScheduler_Action $action Action to be validated.
     * @throws InvalidArgumentException When json encoded args is too long.
     */
    protected function validate_action(\WPStaging\Vendor\ActionScheduler_Action $action)
    {
        if (\strlen(\json_encode($action->get_args())) > static::$max_args_length) {
            throw new \InvalidArgumentException(\sprintf(\WPStaging\Vendor\__('ActionScheduler_Action::$args too long. To ensure the args column can be indexed, action args should not be more than %d characters when encoded as JSON.', 'action-scheduler'), static::$max_args_length));
        }
    }
    /**
     * Cancel pending actions by hook.
     *
     * @since 3.0.0
     *
     * @param string $hook Hook name.
     *
     * @return void
     */
    public function cancel_actions_by_hook($hook)
    {
        $action_ids = \true;
        while (!empty($action_ids)) {
            $action_ids = $this->query_actions(array('hook' => $hook, 'status' => self::STATUS_PENDING, 'per_page' => 1000));
            $this->bulk_cancel_actions($action_ids);
        }
    }
    /**
     * Cancel pending actions by group.
     *
     * @since 3.0.0
     *
     * @param string $group Group slug.
     *
     * @return void
     */
    public function cancel_actions_by_group($group)
    {
        $action_ids = \true;
        while (!empty($action_ids)) {
            $action_ids = $this->query_actions(array('group' => $group, 'status' => self::STATUS_PENDING, 'per_page' => 1000));
            $this->bulk_cancel_actions($action_ids);
        }
    }
    /**
     * Cancel a set of action IDs.
     *
     * @since 3.0.0
     *
     * @param array $action_ids List of action IDs.
     *
     * @return void
     */
    private function bulk_cancel_actions($action_ids)
    {
        foreach ($action_ids as $action_id) {
            $this->cancel_action($action_id);
        }
        \WPStaging\Vendor\do_action('action_scheduler_bulk_cancel_actions', $action_ids);
    }
    /**
     * @return array
     */
    public function get_status_labels()
    {
        return array(self::STATUS_COMPLETE => \WPStaging\Vendor\__('Complete', 'action-scheduler'), self::STATUS_PENDING => \WPStaging\Vendor\__('Pending', 'action-scheduler'), self::STATUS_RUNNING => \WPStaging\Vendor\__('In-progress', 'action-scheduler'), self::STATUS_FAILED => \WPStaging\Vendor\__('Failed', 'action-scheduler'), self::STATUS_CANCELED => \WPStaging\Vendor\__('Canceled', 'action-scheduler'));
    }
    /**
     * Check if there are any pending scheduled actions due to run.
     *
     * @param ActionScheduler_Action $action
     * @param DateTime $scheduled_date (optional)
     * @return string
     */
    public function has_pending_actions_due()
    {
        $pending_actions = $this->query_actions(array('date' => \WPStaging\Vendor\as_get_datetime_object(), 'status' => \WPStaging\Vendor\ActionScheduler_Store::STATUS_PENDING));
        return !empty($pending_actions);
    }
    /**
     * Callable initialization function optionally overridden in derived classes.
     */
    public function init()
    {
    }
    /**
     * Callable function to mark an action as migrated optionally overridden in derived classes.
     */
    public function mark_migrated($action_id)
    {
    }
    /**
     * @return ActionScheduler_Store
     */
    public static function instance()
    {
        if (empty(self::$store)) {
            $class = \WPStaging\Vendor\apply_filters('action_scheduler_store_class', self::DEFAULT_CLASS);
            self::$store = new $class();
        }
        return self::$store;
    }
}
