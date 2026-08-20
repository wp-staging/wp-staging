<?php

namespace WPStaging\Framework\Traits;

trait BooleanTransientTrait
{
    abstract function getTransientName();

    abstract function getExpiryTime();




    public function setTransient()
    {
        set_transient($this->getTransientName(), true, $this->getExpiryTime());
    }




    public function getTransient()
    {
        return get_transient($this->getTransientName());
    }




    public function deleteTransient()
    {
        delete_transient($this->getTransientName());
    }
}
