<?php

namespace WPStaging\Framework\Traits;

trait BooleanTransientTrait
{
    abstract function getTransientName();

    abstract function getExpiryTime();

    /**
     * Set the initial transient to value to true
     */
    public function setTransient()
    {
        set_transient($this->getTransientName(), true, $this->getExpiryTime());
    }

    /**
     * @return bool
     */
    public function getTransient()
    {
        return get_transient($this->getTransientName());
    }

    /**
     * Delete the transient
     */
    public function deleteTransient()
    {
        delete_transient($this->getTransientName());
    }
}
