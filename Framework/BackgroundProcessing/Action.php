<?php

/**
 * Models the information about an Action stored in the Queue.
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */

namespace WPStaging\Framework\BackgroundProcessing;

use BadMethodCallException;
use WPStaging\Framework\BackgroundProcessing\Exceptions\QueueException;

/**
 * Class Action
 *
 * @package WPStaging\Framework\BackgroundProcessing
 *
 * @property int        $id            The Action id, the unique, auto-increment value identifying its row.
 * @property string     $action        The action name.
 * @property string     $jobId         The Job the action belongs to.
 * @property int        $priority      The Action priority, lower is executed first (like WP Filters API).
 * @property array      $args          A set of arguments for the action.
 * @property string     $status        The current Action status in the context of the Queue.
 * @property string     $claimedAt     The date and time, in the site timezone, the Action was last claimed for processing.
 * @property string     $updatedAt     The date and time, in the site timezone, the Action was last updated.
 * @property mixed|null $custom        Custom data attached to the Action.
 */
class Action
{
    use WithQueueAwareness;

    /**
     * The Action id, the unique, auto-increment value identifying its row.
     *
     * @var int
     */
    private $id;

    /**
     * The action name, it can be a string that will be processed as a WordPress action or
     * another type of callable that will be called directly.
     *
     * @var string
     */
    private $action;

    /**
     * The name of the Job, or Group, the Action belongs to.
     *
     * @var string
     */
    private $jobId;

    /**
     * The Action priority, works like the WordPress Filter API priority where
     * lower values are processed first.
     *
     * @var int
     */
    public $priority;

    /**
     * An optional array of arguments that will be passed to either the WordPress
     * action fired by the Queue or as parameters for the invoked callable.
     *
     * @var array
     */
    private $args;

    /**
     * A string representing the Action status in the context of the Queue, e.g. ready
     * or processing.
     *
     * @var string|null
     */
    public $status;

    /**
     * The string representing the date and time, in the site timezone, the Action was
     * last claimed for processing.
     *
     * @var string|null
     */
    private $claimedAt;

    /**
     * The string representing the date and time, in the site timezone, the Action was
     * last updated in any way.
     *
     * @var string|null
     */
    public $updatedAt;

    /**
     * Custom data attached to the Action.
     *
     * @var mixed|null
     */
    private $custom;

    /**
     * Action constructor.
     *
     * @param int          $id        The Action id, its unique identifier in the Queue; `0` is a valid id
     *                                for provisional Actions.
     * @param string       $action    The Action name, it could be a string that will be used to fire a WP
     *                                action, or a string in the format `<class>::<static-method>` that will
     *                                cause that static method to be invoked directly with the Action arguments.
     * @param array $args      An optional set of arguments for the Action that will either be passed to the
     *                                invoked WP action, or to the specified static method as parameters.
     * @param string       $jobId     The Job, or Group, the Action belongs to.
     * @param int          $priority  The Action priority in the context of the Queue, it works like the priority of
     *                                filters in the WordPress Filter API: lower values are processed first.
     * @param string|null  $status    The Action status in the context of the Queue, e.g. ready or processing.
     * @param string|null  $claimedAt The string representing the date and time, in the site timezone, the Action was last claimed
     *                                for processing.
     * @param string|null  $updatedAt The string representing the date and time, in the site timezone, the Action was last updated.
     * @param mixed|null  $custom    Custom data attached to the Action.
     *
     * @throws QueueException If any value used to build the Action is not valid.
     */
    public function __construct(
        $id,
        $action,
        array $args = [],
        $jobId = 'default',
        $priority = 0,
        $status = null,
        $claimedAt = null,
        $updatedAt = null,
        $custom = null
    ) {
        if (!is_numeric($id) && absint($id) == $id) {
            throw new QueueException('Id MUST be a positive integer.');
        }

        if ((string)$action === '') {
            throw new QueueException('Action MUST be a non-empty string.');
        }

        if ((string)$jobId === '') {
            throw new QueueException('Job ID MUST be a non-empty string.');
        }

        $priority = is_numeric($priority) && (int)$priority == $priority ?
            (int)$priority
            : $this->getDefaultPriority();

        $this->id = $id;
        $this->action = $action;
        $this->args = $args;
        $this->jobId = $jobId;
        $this->priority = $priority;
        $this->status = $status;
        $this->claimedAt = $claimedAt;
        $this->updatedAt = $updatedAt;
        $this->custom = $custom;
    }

    /**
     * Builds, and returns, an Action instance from the raw data found in a database row.
     *
     * @param array<string,mixed> $dbRow The database row, as an associative array.
     *
     * @return Action A reference to an Action instance built on the row data.
     *
     * @throws QueueException If there's any issue validating any one of the row fields.
     */
    public static function fromDbRow(array $dbRow)
    {
        $id = (int)$dbRow['id'];
        $action = (string)$dbRow['action'];
        $jobId = isset($dbRow['jobId']) ? (string)($dbRow['jobId']) : null;
        $priority = isset($dbRow['priority']) ? (int)$dbRow['priority'] : self::getDefaultPriority();
        $args = isset($dbRow['args']) ? (array)maybe_unserialize($dbRow['args']) : [];
        $status = isset($dbRow['status']) ? (string)$dbRow['status'] : Queue::STATUS_READY;
        $claimedAt = isset($dbRow['claimed_at']) ? (string)$dbRow['claimed_at'] : null;
        $updatedAt = isset($dbRow['updated_at']) ? (string)$dbRow['updated_at'] : null;
        $custom = isset($dbRow['custom']) ? maybe_unserialize($dbRow['custom']) : null;

        return new self($id, $action, $args, $jobId, $priority, $status, $claimedAt, $updatedAt, $custom);
    }

    /**
     * Overrides the magic method to get private properties with a read-only API.
     *
     * @param string $name The name of the property to get.
     *
     * @return mixed The property value.
     *
     * @throws BadMethodCallException If the Action does not define the property.
     */
    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            throw new BadMethodCallException("The Action object does not have an accessible property '{$name}'.");
        }

        return isset($this->{$name}) ? $this->{$name} : null;
    }

    /**
     * Overrides the magic method to clearly signal the immutable nature of the Action object.
     *
     * @param string $name The name of the property to set.
     * @param mixed $value The value that should be assigned to the property.
     *
     * @throws BadMethodCallException As Actions are immutable.
     */
    public function __set($name, $value)
    {
        throw new BadMethodCallException("The Action object is immutable: its properties can be set only when building it.");
    }

    /**
     * Returns whether two Actions are the same in regard to relevant properties.
     *
     * @param Action        $toCompare            A reference to the Action instance this one should
     *                                            be compared to.
     * @param array<string> $compareFieldsExclude A list of Action fields that should not  be used in the
     *                                            comparison; by default all the Action properties will
     *                                            be used
     *
     * @return bool Whether this Action and the one it's being compared to are equals
     *              or not.
     */
    public function equals(Action $toCompare, array $compareFieldsExclude = [])
    {
        $compareFields = array_diff(
            ['id', 'action', 'jobId', 'priority', 'args','status'],
            $compareFieldsExclude
        );

        foreach ($compareFields as $prop) {
            if (!$this->{$prop} == $toCompare->{$prop}) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the associative array representation of the Action.
     *
     * @return array<string,string|int|array> A map from the Action current properties
     *                                        to their current values.
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'jobId' => $this->jobId,
            'priority' => $this->priority,
            'args' => $this->args,
            'status' => $this->status,
            'claimedAt' => $this->claimedAt,
            'updatedAt' => $this->updatedAt,
            'custom' => $this->custom
        ];
    }

    /**
     * Alters the Action instance with a set of alterations. Since the Action is immutable
     * alteration will produce, in fact, a clone of it that will be returned.
     *
     * @param array<string,mixed> $alterations A map from the alteration keys to their
     *                                         values.
     *
     * @return Action A reference to a modified clone of the current Action.
     */
    public function alter(array $alterations)
    {
        $clone = clone $this;

        foreach ($alterations as $key => $value) {
            $clone->{$key} = $value;
        }

        return $clone;
    }

    /**
     * Utility method to sort Action in functions or methods accepting
     * sorting callbacks like `usort`.
     *
     * The method will sort Actions by priority, action and jobId, in
     * ascending order.
     *
     * @param array<string,mixed>|Action $actionOne Either an Action instance
     *                                              or the array representation of an
     *                                              Action.
     * @param array<string,mixed>|Action $actionTwo Either an Action instance
     *                                              or the array representation of an
     *                                              Action.
     *
     * @return int An integer sticking with `usort` expected value that will return `-1`
     *             if the first Action comes before the second, `0` if they are equal
     *             in all regards and `1` if the second Action comes before the first.
     */
    public static function sort($actionOne, $actionTwo)
    {
        $objectOne = (object)$actionOne;
        $objectTwo = (object)$actionTwo;

        if ($objectOne->priority !== $objectTwo->priority) {
            return $objectOne->priority > $objectTwo->priority ? 1 : -1;
        }

        if ($objectOne->action !== $objectTwo->action) {
            return $objectOne->action > $objectTwo->action ? 1 : -1;
        }

        if ($objectOne->jobId !== $objectTwo->jobId) {
            return $objectOne->jobId > $objectTwo->jobId ? 1 : -1;
        }

        return 0;
    }
}
