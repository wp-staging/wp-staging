<?php

namespace WPStaging\Framework\Interfaces;

interface TransientInterface
{
    /**
     * @return string
     */
    public function getTransientName();

    /**
     * @return int expiry time
     */
    public function getExpiryTime();

    /**
     * Set the initial transient with some value
     */
    public function setTransient();

    /**
     * @return bool
     */
    public function getTransient();

    /**
     * Delete the transient
     */
    public function deleteTransient();
}
