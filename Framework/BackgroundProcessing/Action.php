<?php







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






    private $id;







    private $action;






    private $jobId;







    public $priority;







    private $args;







    public $status;







    private $claimedAt;







    public $updatedAt;






    private $custom;






    private $response;






















    public function __construct(
        $id,
        $action,
        array $args = [],
        $jobId = 'default',
        $priority = 0,
        $status = null,
        $claimedAt = null,
        $updatedAt = null,
        $custom = null,
        $response = null
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

        $this->id        = $id;
        $this->action    = $action;
        $this->args      = $args;
        $this->jobId     = $jobId;
        $this->priority  = $priority;
        $this->status    = $status;
        $this->claimedAt = $claimedAt;
        $this->updatedAt = $updatedAt;
        $this->custom    = $custom;
        $this->response  = $response;
    }










    public static function fromDbRow(array $dbRow)
    {
        $id        = (int)$dbRow['id'];
        $action    = (string)$dbRow['action'];
        $jobId     = isset($dbRow['jobId']) ? (string)($dbRow['jobId']) : null;
        $priority  = isset($dbRow['priority']) ? (int)$dbRow['priority'] : self::getDefaultPriority();
        $args      = isset($dbRow['args']) ? (array)maybe_unserialize($dbRow['args']) : [];
        $status    = isset($dbRow['status']) ? (string)$dbRow['status'] : Queue::STATUS_READY;
        $claimedAt = isset($dbRow['claimed_at']) ? (string)$dbRow['claimed_at'] : null;
        $updatedAt = isset($dbRow['updated_at']) ? (string)$dbRow['updated_at'] : null;
        $custom    = isset($dbRow['custom']) ? maybe_unserialize($dbRow['custom']) : null;
        $response  = isset($dbRow['response']) ? maybe_unserialize($dbRow['response']) : null;

        return new self($id, $action, $args, $jobId, $priority, $status, $claimedAt, $updatedAt, $custom, $response);
    }










    public function __get($name)
    {
        if (!property_exists($this, $name)) {
            throw new BadMethodCallException("The Action object does not have an accessible property '{$name}'.");
        }

        return isset($this->{$name}) ? $this->{$name} : null;
    }









    public function __set($name, $value)
    {
        throw new BadMethodCallException("The Action object is immutable: its properties can be set only when building it.");
    }













    public function equals(Action $toCompare, array $compareFieldsExclude = [])
    {
        $compareFields = array_diff(
            ['id', 'action', 'jobId', 'priority', 'args', 'status'],
            $compareFieldsExclude
        );

        foreach ($compareFields as $prop) {
            if (!$this->{$prop} == $toCompare->{$prop}) {
                return false;
            }
        }

        return true;
    }







    public function toArray()
    {
        return [
            'id'        => $this->id,
            'action'    => $this->action,
            'jobId'     => $this->jobId,
            'priority'  => $this->priority,
            'args'      => $this->args,
            'status'    => $this->status,
            'claimedAt' => $this->claimedAt,
            'updatedAt' => $this->updatedAt,
            'custom'    => $this->custom,
            'response'  => $this->response,
        ];
    }










    public function alter(array $alterations)
    {
        $clone = clone $this;

        foreach ($alterations as $key => $value) {
            $clone->{$key} = $value;
        }

        return $clone;
    }



















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
