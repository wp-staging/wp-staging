<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Framework\Analytics\AnalyticsEventDto;

/**
 * Class Cancel Update Processing
 * @package WPStaging\Backend\Modules\Jobs
 */
class CancelUpdate extends Job
{

    /**
     * Start Module
     * @return bool
     */
    public function start()
    {
        $cloneData = $this->createCloneData();

        if (!empty($this->options->jobIdentifier)) {
            AnalyticsEventDto::enqueueCancelEvent($this->options->jobIdentifier);
        }

        if (empty($cloneData)) {
            return true;
        }
        // Delete Cache Files
        $this->deleteCacheFiles();

        $this->returnFinish();

        return true;
    }

    /**
     * @return array
     */
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

    /**
     * @return bool
     */
    public function check()
    {
        return (
                isset($this->options) &&
                isset($this->options->clone) &&
                isset($this->options->cloneNumber) &&
                isset($this->options->cloneDirectoryName) &&
                isset($_POST["clone"]) &&
                $_POST["clone"] === $this->options->clone
                );
    }

    /**
     * Get json response
     * return json
     */
    private function returnFinish($message = '')
    {

        wp_die(json_encode([
            'job' => 'delete',
            'status' => true,
            'message' => $message,
            'error' => false,
            'delete' => 'finished'
        ]));
    }


    /**
     * Delete Cache Files
     */
    protected function deleteCacheFiles()
    {
        $this->log("Cancel Updating: Deleting clone job's cache files...");

        // Clean cache files
        $this->cache->delete("clone_options");
        $this->cache->delete("files_to_copy");

        $this->log("Updating process canceled");
    }
}
