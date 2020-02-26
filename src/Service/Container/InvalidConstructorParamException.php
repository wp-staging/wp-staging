<?php

namespace WPStaging\Service\Container;

use Exception;
use Throwable;

class InvalidConstructorParamException extends Exception
{

    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Invalid Constructor Parameter $%s', $message);
        parent::__construct($message, $code, $previous);
    }
}
