<?php
namespace WPStaging\Backend\Modules\Jobs\Interfaces;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

/**
 * Interface JobInterface
 * @package WPStaging\Backend\Modules\Jobs\Interfaces
 */
interface JobInterface
{

    /**
     * Start Module
     * @return bool
     */
    public function start();

    /**
     * Next Step of the Job
     * @return void
     */
    public function next();
}