<?php
namespace WPStaging\Backend\Modules\Jobs;

/**
 * Class Cancel
 * @package WPStaging\Backend\Modules\Jobs
 */
class Cancel extends Job
{
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
     * @return array
     */
    protected function createCloneData()
    {
        $clone = array();

        if (!$this->check())
        {
            return $clone;
        }

        $clone["name"]      = $this->options->clone;
        $clone["number"]    = $this->options->cloneNumber;
        $clone["path"]      = ABSPATH . $this->options->cloneDirectoryName;

        return $clone;
    }

    /**
     * Start Module
     * @return bool
     */
    public function start()
    {
        $cloneData = $this->createCloneData();

        if (empty($cloneData))
        {
            return true;
        }

        $delete = new Delete();
        return $delete->start($cloneData);
    }
}