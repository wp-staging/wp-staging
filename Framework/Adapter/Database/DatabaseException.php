<?php

namespace WPStaging\Framework\Adapter\Database;

use Exception;

class DatabaseException extends Exception
{

    public function __construct($message = '')
    {
        parent::__construct($message);
    }
}
