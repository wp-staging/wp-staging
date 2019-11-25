<?php
namespace WPStaging\Backend\Modules\Jobs\Exceptions;

/**
 * Class JobNotFoundException
 * @package WPStaging\Backend\Modules\Jobs\Exceptions
 */
class JobNotFoundException extends \Exception
{
    /**
     * @var string
     */
    protected $message = "Can't execute te job; Job's method %s is not found";

    /**
     * JobNotFoundException constructor.
     * @param string $className
     */
    public function __construct($className = "")
    {
        $this->message = sprintf($this->message, $className);
    }
}