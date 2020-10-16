<?php

namespace WPStaging\Framework;

use Exception;

class InvalidPluginException extends Exception
{

    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        $message = sprintf('Plugin %s must implement PluginInterface', $message);
        parent::__construct($message, $code, $previous);
    }
}
