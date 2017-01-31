<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

use WPStaging\Backend\Modules\Jobs\Interfaces\JobInterface;

/**
 * Class Files
 * @package WPStaging\Backend\Modules\Jobs
 */
class Files implements JobInterface
{

    public function __construct()
    {

    }

    /**
     * Start Module
     * @return mixed
     */
    public function start()
    {
        // TODO: Implement start() method.
    }
}