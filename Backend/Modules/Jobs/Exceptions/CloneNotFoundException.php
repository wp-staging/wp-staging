<?php

namespace WPStaging\Backend\Modules\Jobs\Exceptions;





class CloneNotFoundException extends \Exception
{



    protected $message = "Clone name is not set or clone not found";
}
