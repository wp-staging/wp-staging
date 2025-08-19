<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
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

        $this->deleteCacheFiles();

        $deleteJob = WPStaging::make(Delete::class);
        $deleteJob->setIsExternalDb(!$this->isStagingDatabaseSameAsProductionDatabase());

        return $deleteJob->start($cloneData);
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
     * @return void
     */
    private function deleteCacheFiles()
    {
        if ($this->cloneOptionCache->isValid()) {
            $this->cloneOptionCache->delete();
        }

        if ($this->filesIndexCache->isValid()) {
            $this->filesIndexCache->delete();
        }
    }
}
