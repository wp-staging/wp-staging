<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingCreate;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingReset;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingUpdate;
use WPStaging\Framework\Logger\EventLoggerConst;
use WPStaging\Staging\Sites;
use WPStaging\Framework\Traits\EventLoggerTrait;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Staging\Jobs\StagingSiteCreate;





class Finish extends Job
{
    use EventLoggerTrait;





    private $clone = '';




    private $urls;






    public function start()
    {
        $this->urls = WPStaging::make(Urls::class);

 
        $this->clone = preg_replace("#\W+#", '-', strtolower($this->options->clone));

        $this->deleteCacheFiles();

 
        $this->prepareCloneDataRecords();

        $this->options->isRunning = false;

        $return = [
            "directoryName" => $this->options->cloneDirectoryName,
            "path"          => trailingslashit($this->options->destinationDir),
            "url"           => $this->getDestinationUrl(),
            "number"        => $this->options->cloneNumber,
            "version"       => WPStaging::getVersion(),
            "status"        => 'finished',
            "prefix"        => $this->options->prefix,
            "last_msg"      => $this->logger->getLastLogMsg(),
            "job"           => $this->options->currentJob,
            "percentage"    => 100,
        ];

        $processType    = EventLoggerConst::PROCESS_PREFIX_CLONE;
        $successMessage = "Staging site successfully created";
        switch ($this->options->mainJob) {
            case Job::STAGING:
                WPStaging::make(AnalyticsStagingCreate::class)->enqueueFinishEvent($this->options->jobIdentifier, $this->options);
                do_action(StagingSiteCreate::ACTION_STAGING_SITE_CREATED, $this->options);
                break;
            case Job::UPDATE:
                $processType    = EventLoggerConst::PROCESS_PREFIX_CLONE_UPDATE;
                $successMessage = 'Staging site successfully updated';
                WPStaging::make(AnalyticsStagingUpdate::class)->enqueueFinishEvent($this->options->jobIdentifier, $this->options);
                break;
            case Job::RESET:
                $processType    = EventLoggerConst::PROCESS_PREFIX_CLONE_RESET;
                $successMessage = 'Staging site successfully reset';
                WPStaging::make(AnalyticsStagingReset::class)->enqueueFinishEvent($this->options->jobIdentifier, $this->options);
                break;
        }

        do_action(StagingSiteCreate::ACTION_CLONING_COMPLETE, $this->options);

        $this->logger->info("✓ " . $successMessage);
        $this->logCloneCompleted($processType);
        return (object) $return;
    }





    protected function deleteCacheFiles()
    {
        $this->log("Finish: Deleting clone job's cache files...");

        $this->cloneOptionCache->delete();
        $this->filesIndexCache->delete();

        $this->log("Finish: Clone job's cache files have been deleted!");
    }






    protected function prepareCloneDataRecords()
    {
 
        $this->log("Finish: Verifying existing clones...");

 
        if (isset($this->options->existingClones[$this->options->clone])) {
            if ($this->isMultisiteAndPro()) {
                $this->options->existingClones[$this->options->clone]['url'] = $this->getDestinationUrl();
            }

            $this->options->existingClones[$this->options->clone]['datetime']                = time();
            $this->options->existingClones[$this->options->clone]['status']                  = 'finished';
            $this->options->existingClones[$this->options->clone]['prefix']                  = $this->options->prefix;
            $this->options->existingClones[$this->options->clone]['useCustomDatabase']       = $this->isExternalDatabase();
            $this->options->existingClones[$this->options->clone]['isCronEnabled']           = empty($this->options->isCronEnabled) ? false : true;
            $this->options->existingClones[$this->options->clone]['isEmailsAllowed']         = (bool) $this->options->isEmailsAllowed;
            $this->options->existingClones[$this->options->clone]['uploadsSymlinked']        = (bool) $this->options->uploadsSymlinked;
            $this->options->existingClones[$this->options->clone]['includedTables']          = $this->options->tables;
            $this->options->existingClones[$this->options->clone]['excludeSizeRules']        = $this->options->excludeSizeRules;
            $this->options->existingClones[$this->options->clone]['excludeGlobRules']        = $this->options->excludeGlobRules;
            $this->options->existingClones[$this->options->clone]['excludedDirectories']     = $this->options->excludedDirectories;
            $this->options->existingClones[$this->options->clone]['extraDirectories']        = $this->options->extraDirectories;
            $this->options->existingClones[$this->options->clone]['isWooSchedulerEnabled']   = empty($this->options->isWooSchedulerEnabled) ? false : true;
            $this->options->existingClones[$this->options->clone]['isEmailsReminderEnabled'] = empty($this->options->isEmailsReminderEnabled) ? false : true;
            $this->options->existingClones[$this->options->clone]['isAutoUpdatePlugins']     = empty($this->options->isAutoUpdatePlugins) ? false : true;
            update_option(Sites::STAGING_SITES_OPTION, $this->options->existingClones, false);
            $this->log("Finish: The job finished!");
            return true;
        }

        $this->log("Finish: {$this->options->clone}'s clone job's data is not in database, generating data");

        $this->options->existingClones[$this->clone] = [
            "directoryName"       => $this->options->cloneDirectoryName,
            "path"                => trailingslashit($this->options->destinationDir),
            "url"                 => $this->getDestinationUrl(),
            "number"              => $this->options->cloneNumber,
            "version"             => WPStaging::getVersion(),
            "status"              => "finished",
            "prefix"              => $this->options->prefix,
            "datetime"            => time(),
            "useCustomDatabase"   => $this->isExternalDatabase(),
            "databaseUser"        => $this->options->databaseUser,
            "databasePassword"    => $this->options->databasePassword,
            "databaseDatabase"    => $this->options->databaseDatabase,
            "databaseServer"      => $this->options->databaseServer,
            "databasePrefix"      => $this->options->databasePrefix,
            "databaseSsl"         => (bool)$this->options->databaseSsl,
            "isEmailsAllowed"     => (bool) $this->options->isEmailsAllowed,
            "uploadsSymlinked"    => (bool) $this->options->uploadsSymlinked,
            "includedTables"      => $this->options->tables,
            "excludeSizeRules"    => $this->options->excludeSizeRules,
            "excludeGlobRules"    => $this->options->excludeGlobRules,
            "excludedDirectories" => $this->options->excludedDirectories,
            "extraDirectories"    => $this->options->extraDirectories,
            "networkClone"        => $this->isNetworkClone(),
        ];

        if (update_option(Sites::STAGING_SITES_OPTION, $this->options->existingClones) === false) {
            $this->log("Finish: Failed to save {$this->options->clone}'s clone job data to database'");
            return false;
        }

        return true;
    }





    private function getDestinationUrl()
    {
        if (!empty($this->options->cloneHostname)) {
            return $this->options->cloneHostname;
        }

 
        if (!$this->isMultisiteAndPro()) {
            return trailingslashit(get_site_url()) . $this->options->cloneDirectoryName;
        }

 
        $multisitePath = defined('PATH_CURRENT_SITE') ? PATH_CURRENT_SITE : '/';
        return rtrim($this->urls->getBaseUrl(), '/\\') . $multisitePath . $this->options->cloneDirectoryName;
    }
}
