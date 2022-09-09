<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;

//use WPStaging\Core\Utils\Cache;

/**
 * Class Cloning
 * @package WPStaging\Backend\Modules\Jobs
 */
class ProcessLock extends JobExecutable
{

    /**
     * Check if any process is already running
     * @return boolean
     */
    public function isRunning()
    {
        // Another process is running
        if (parent::isRunning()) {
            $this->log("Another process is running");

            $message = __('Hold on, another WP STAGING process is already running...', 'wp-staging');

            require_once WPSTG_PLUGIN_DIR . "Backend/views/clone/ajax/process-lock.php";

            wp_die();
        }
        // No other process running

        return false;
    }

    /**
     * Check if any process is already running, if running return a json encoded response for Swal Modal,
     * Otherwise return false
     *
     * @return boolean|string
     */
    public function ajaxIsRunning()
    {
        if (parent::isRunning()) {
            return [
                'success'       => false,
                'type'          => 'processLock',
                // TODO: Create a Swal Response Class and Js library to handle that response or, Implement own Swal alternative
                'swalOptions'   => [
                    'title'             => __('Error!', 'wp-staging'),
                    'html'              => __('Hold on, another WP STAGING process is already running...', 'wp-staging'),
                    'confirmButtonText' => __('Stop other process', 'wp-staging'),
                    'showCancelButton'  => true,
                ],
            ];
        }

        return false;
    }

    /**
     * remove process lock value
     */
    public function restart()
    {
        unset($this->options->isRunning);
        $this->cache->delete("clone_options");
        $this->cache->delete("files_to_copy");
    }

    /**
     * abstract
     * @return void
     */
    protected function calculateTotalSteps()
    {
    }

    /**
     * abstract
     * @return bool
     */
    protected function execute()
    {
        return false;
    }
}
