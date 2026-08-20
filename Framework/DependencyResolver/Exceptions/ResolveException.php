<?php

namespace WPStaging\Framework\DependencyResolver\Exceptions;

abstract class ResolveException extends \RuntimeException
{



    private $item;




    private $dependency;





    public function __construct($item, $dependency, $message, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->item = $item;
        $this->dependency = $dependency;
    }




    public function getItem()
    {
        return $this->item;
    }




    public function getDependency()
    {
        return $this->dependency;
    }
}
