<?php

namespace WPStaging\Framework\Container;

use Exception;

class InvalidConstructorParamException extends Exception
{

    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        $message = sprintf('Invalid Constructor Parameter $%s', $message);
        parent::__construct($message, $code, $previous);
    }
}
