<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;

//use WPStaging\Core\Utils\Cache;

/**
 * Class Cloning
 * @package WPStaging\Backend\Modules\Jobs
 */
class ProcessLock extends JobExecutable {

    /**
     * Check if any process is already running
     * @return boolean
     */
    public function isRunning() {
        // Another process is running
        if (parent::isRunning()) {

            $this->log( "Another process is running" );

            $message = __( 'Hold on, another WP Staging process is already running...', 'wp-staging' );

            require_once WPSTG_PLUGIN_DIR . "Backend/views/clone/ajax/process-lock.php";

            wp_die();
        }
        // No other process running

        return false;
    }

    /**
     * remove process lock value
     */
    public function restart() {
        unset( $this->options->isRunning );
        $this->cache->delete( "clone_options" );
        $this->cache->delete( "files_to_copy" );
    }

    /**
     * abstract
     * @return void
     */
    protected function calculateTotalSteps() {

    }

    /**
     * abstract
     * @return bool
     */
    protected function execute() {

    }

}
