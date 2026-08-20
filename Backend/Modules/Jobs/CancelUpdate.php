<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Framework\Analytics\AnalyticsEventDto;





class CancelUpdate extends Job
{





    public function start()
    {
        $cloneData = $this->createCloneData();

        if (!empty($this->options->jobIdentifier)) {
            AnalyticsEventDto::enqueueCancelEvent($this->options->jobIdentifier);
        }

        if (empty($cloneData)) {
            return true;
        }

 
        $this->deleteCacheFiles();

        $this->returnFinish();

        return true;
    }




    protected function createCloneData()
    {
        $clone = [];

        if (!$this->check()) {
            return $clone;
        }

        $clone["name"] = $this->options->clone;
        $clone["number"] = $this->options->cloneNumber;
        $clone["path"] = ABSPATH . $this->options->cloneDirectoryName;
        $clone["prefix"] = ABSPATH . $this->options->prefix;

        return $clone;
    }




    public function check()
    {
        return (
                isset($this->options->clone) &&
                isset($this->options->cloneNumber) &&
                isset($this->options->cloneDirectoryName) &&
                isset($_POST["clone"]) &&
                $_POST["clone"] === $this->options->clone
                );
    }





    private function returnFinish($message = '')
    {

        wp_die(json_encode([
            'job'     => 'delete',
            'status'  => true,
            'message' => $message,
            'error'   => false,
            'delete'  => 'finished',
        ]));
    }





    protected function deleteCacheFiles()
    {
        $this->log("Cancel Updating: Deleting clone job's cache files...");

 
        $this->cloneOptionCache->delete();
        $this->filesIndexCache->delete();

        $this->log("Updating process canceled");
    }
}
