<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Framework\Facades\Sanitize;





class Logs extends Job
{
    private $clone = null;




    public function initialize()
    {
        if (isset($_POST["clone"])) {
            $this->clone = Sanitize::sanitizeString($_POST["clone"]);
        }
    }




    protected function getCloneFileName()
    {
        return ($this->clone === null) ? $this->options->clone : $this->clone;
    }




    public function setClone($clone)
    {
        $this->clone = $clone;
    }





    public function start()
    {
        $logs = explode(PHP_EOL, $this->logger->read($this->getCloneFileName()));
        return trim(implode("<br>", array_reverse($logs)), "<br>");
    }
}
