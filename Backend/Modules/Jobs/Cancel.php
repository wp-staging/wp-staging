<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Framework\Analytics\AnalyticsEventDto;

/**
 * Class Cancel Processing
 * @package WPStaging\Backend\Modules\Jobs
 */
class Cancel extends Job
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
       // Delete data in external database
        if (empty($this->options->databaseUser)) {
            $delete = new Delete();
        } else {
            $delete = new Delete(true);
        }
        return $delete->start($cloneData);
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

        $clone["name"]             = $this->options->clone;
        $clone["number"]           = $this->options->cloneNumber;
        $clone["path"]             = ABSPATH . $this->options->cloneDirectoryName;
        $clone["prefix"]           = $this->options->prefix;
        $clone["databaseServer"]   = $this->options->databaseServer;
        $clone["databaseUser"]     = $this->options->databaseUser;
        $clone["databasePassword"] = $this->options->databasePassword;
        $clone["databasePrefix"]   = $this->options->databasePrefix;
        $clone["databaseDatabase"] = $this->options->databaseDatabase;
        $clone["databaseSsl"]      = $this->options->databaseSsl;

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
          'job'     => 'delete',
          'status'  => true,
          'message' => $message,
          'error'   => false,
          'delete'  => 'finished'
        ]));
    }
}
