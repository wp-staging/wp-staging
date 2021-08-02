<?php

namespace WPStaging\Framework\DependencyResolver\Exceptions;

class MissingReferenceException extends ResolveException
{
    /**
     * @param int|string $item
     * @param int|string $dependency
     */
    public function __construct($item, $dependency, $code = 0, $previous = null)
    {
        parent::__construct($item, $dependency, sprintf('Missing dependency: %s -> %s', $item, $dependency), $code, $previous);
    }
}
