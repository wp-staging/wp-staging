<?php

namespace WPStaging\Backend\Modules\Jobs;

/**
 * Class Cancel Processing
 * @package WPStaging\Backend\Modules\Jobs
 */
class Cancel extends Job {

    /**
     * Start Module
     * @return bool
     */
    public function start() {
        $cloneData = $this->createCloneData();

        if (empty($cloneData)) {
            return true;
        }


        $delete = new Delete();
        return $delete->start($cloneData);
    }

    /**
     * @return array
     */
    protected function createCloneData() {
        $clone = array();

        if (!$this->check()) {
            return $clone;
        }

        $clone["name"] = $this->options->clone;
        $clone["number"] = $this->options->cloneNumber;
        $clone["path"] = ABSPATH . $this->options->cloneDirectoryName;
        $clone["prefix"] = $this->options->prefix;

        return $clone;
    }

    /**
     * @return bool
     */
    public function check() {
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
    private function returnFinish($message = '') {

        wp_die(json_encode(array(
            'job' => 'delete',
            'status' => true,
            'message' => $message,
            'error' => false,
            'delete' => 'finished'
        )));
    }

}
