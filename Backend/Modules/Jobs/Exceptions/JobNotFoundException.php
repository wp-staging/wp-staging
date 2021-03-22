<?php

namespace WPStaging\Backend\Modules\Jobs\Exceptions;

/**
 * Class JobNotFoundException
 * @package WPStaging\Backend\Modules\Jobs\Exceptions
 */
class JobNotFoundException extends \Exception
{
    /**
     * JobNotFoundException constructor.
     * @param string $className
     */
    public function __construct($className = "")
    {
        parent::__construct(sprintf("Can't execute job; Job's method %s is not found", $className));
    }
}
