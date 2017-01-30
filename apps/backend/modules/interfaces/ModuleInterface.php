<?php

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

/**
 * Interface WPStaging_ModuleInterface
 */
interface WPStaging_ModuleInterface
{
    /**
     * Start Module
     * @return mixed
     */
    public function start();
}