<?php

namespace WPStaging\Framework\DependencyResolver\Exceptions;

class CircularReferenceException extends ResolveException
{




    public function __construct($item, $dependency, $code = 0, $previous = null)
    {
        parent::__construct($item, $dependency, sprintf('Circular dependency: %s -> %s', $item, $dependency), $code, $previous);
    }
}
