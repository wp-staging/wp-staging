<?php

namespace WPStaging\Framework\DependencyResolver\Exceptions;

abstract class ResolveException extends \RuntimeException
{
    /**
     * @var int|string
     */
    private $item;

    /**
     * @var int|string
     */
    private $dependency;

    /**
     * @param int|string $item
     * @param int|string $dependency
     */
    public function __construct($item, $dependency, $message, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->item = $item;
        $this->dependency = $dependency;
    }

    /**
     * @return int|string
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @return int|string
     */
    public function getDependency()
    {
        return $this->dependency;
    }
}
