<?php

namespace WPStaging\Service;

use Exception;
use Throwable;

class InvalidPluginException extends Exception
{

    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Plugin %s must implement PluginInterface', $message);
        parent::__construct($message, $code, $previous);
    }
}
