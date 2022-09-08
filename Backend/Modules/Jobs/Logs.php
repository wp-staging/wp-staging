<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Framework\Facades\Sanitize;

/**
 * Class Logs
 * @package WPStaging\Backend\Modules\Jobs
 */
class Logs extends Job
{
    private $clone = null;

    /**
     * Initialization
     */
    public function initialize()
    {
        if (isset($_POST["clone"])) {
            $this->clone = Sanitize::sanitizeString($_POST["clone"]);
        }
    }

    /**
     * @return string
     */
    protected function getCloneFileName()
    {
        return ($this->clone === null) ? $this->options->clone : $this->clone;
    }

    /**
     * @param null $clone
     */
    public function setClone($clone)
    {
        $this->clone = $clone;
    }

    /**
     * Start Module
     * @return string
     */
    public function start()
    {
        $logs = explode(PHP_EOL, $this->logger->read($this->getCloneFileName()));
        return trim(implode("<br>", array_reverse($logs)), "<br>");
    }
}
