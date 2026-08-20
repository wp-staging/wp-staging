<?php

namespace WPStaging\Backend\Modules\Jobs;





class ProcessLock extends JobExecutable
{





    public function isRunning()
    {
 
        if (parent::isRunning()) {
            $this->log("Another process is running");

            $message = __('Hold on, another WP STAGING process is already running...', 'wp-staging');

            require_once WPSTG_VIEWS_DIR . "clone/ajax/process-lock.php";

            wp_die();
        }

 

        return false;
    }







    public function ajaxIsRunning()
    {
        if (parent::isRunning()) {
            return [
                'success'     => false,
                'type'        => 'processLock',
 
                'swalOptions' => [
                    'title'             => __('Error!', 'wp-staging'),
                    'html'              => __('Hold on, another WP STAGING process is already running...', 'wp-staging'),
                    'confirmButtonText' => __('Stop other process', 'wp-staging'),
                    'showCancelButton'  => true,
                ],
            ];
        }

        return false;
    }




    public function restart()
    {
        unset($this->options->isRunning);
        $this->cloneOptionCache->delete();
        $this->filesIndexCache->delete();
    }





    protected function calculateTotalSteps()
    {
    }





    protected function execute()
    {
        return false;
    }
}
