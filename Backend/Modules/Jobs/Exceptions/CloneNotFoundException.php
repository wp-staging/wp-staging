<?php

namespace WPStaging\Backend\Modules\Jobs\Exceptions;

/**
 * Class CloneNotFoundException
 * @package WPStaging\Backend\Modules\Jobs\Exceptions
 */
class CloneNotFoundException extends \Exception
{
    /**
     * @var string
     */
    protected $message = "Clone name is not set or clone not found";
}
