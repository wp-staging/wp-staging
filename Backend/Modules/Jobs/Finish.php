<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingCreate;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingReset;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingUpdate;
use WPStaging\Staging\Sites;
use WPStaging\Framework\Traits\EventLoggerTrait;
use WPStaging\Framework\Utils\Urls;

/**
 * Class Finish
 * @package WPStaging\Backend\Modules\Jobs
 */
class Finish extends Job
{
    use EventLoggerTrait;

    /**
     * Clone Key
     * @var string
     */
    private $clone = '';

    /**
     * @var Urls
     */
    private $urls;

    /**
     * Start Module
     * @return object
     * @throws \Exception
     */
    public function start()
    {
        $this->urls = WPStaging::make(Urls::class);

        // sanitize the clone name before saving
        $this->clone = preg_replace("#\W+#", '-', strtolower($this->options->clone));

        $this->deleteCacheFiles();

        // Prepare clone records & save scanned directories for delete job later
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
            "percentage"    => 100
        ];

        switch ($this->options->mainJob) {
            case Job::STAGING:
                WPStaging::make(AnalyticsStagingCreate::class)->enqueueFinishEvent($this->options->jobIdentifier, $this->options);
                break;
            case Job::UPDATE:
                WPStaging::make(AnalyticsStagingUpdate::class)->enqueueFinishEvent($this->options->jobIdentifier, $this->options);
                break;
            case Job::RESET:
                WPStaging::make(AnalyticsStagingReset::class)->enqueueFinishEvent($this->options->jobIdentifier, $this->options);
                break;
        }

        do_action('wpstg_cloning_complete', $this->options);

        $this->logger->info("################## FINISH ##################");
        $this->logCloneCompleted();
        return (object) $return;
    }

    /**
     * Delete Cache Files
     * @throws \Exception
     */
    protected function deleteCacheFiles()
    {
        $this->log("Finish: Deleting clone job's cache files...");

        $this->cloneOptionCache->delete();
        $this->filesIndexCache->delete();

        $this->log("Finish: Clone job's cache files have been deleted!");
    }

    /**
     * Prepare clone records. Without this clone data will not get updated in Sites::STAGING_SITES_OPTION during updating process.
     *
     * @return bool
     */
    protected function prepareCloneDataRecords()
    {
        // Check if clones still exist
        $this->log("Finish: Verifying existing clones...");

        // Clone data already exists
        if (isset($this->options->existingClones[$this->options->clone])) {
            if ($this->isMultisiteAndPro()) {
                $this->options->existingClones[$this->options->clone]['url'] = $this->getDestinationUrl();
            }

            $this->options->existingClones[$this->options->clone]['datetime']              = time();
            $this->options->existingClones[$this->options->clone]['status']                = 'finished';
            $this->options->existingClones[$this->options->clone]['prefix']                = $this->options->prefix;
            $this->options->existingClones[$this->options->clone]['cronDisabled']          = isset($this->options->cronDisabled) ? (bool) $this->options->cronDisabled : false;
            $this->options->existingClones[$this->options->clone]['emailsAllowed']         = (bool) $this->options->emailsAllowed;
            $this->options->existingClones[$this->options->clone]['uploadsSymlinked']      = (bool) $this->options->uploadsSymlinked;
            $this->options->existingClones[$this->options->clone]['includedTables']        = $this->options->tables;
            $this->options->existingClones[$this->options->clone]['excludeSizeRules']      = $this->options->excludeSizeRules;
            $this->options->existingClones[$this->options->clone]['excludeGlobRules']      = $this->options->excludeGlobRules;
            $this->options->existingClones[$this->options->clone]['excludedDirectories']   = $this->options->excludedDirectories;
            $this->options->existingClones[$this->options->clone]['extraDirectories']      = $this->options->extraDirectories;
            $this->options->existingClones[$this->options->clone]['wooSchedulerDisabled']  = (bool) $this->options->wooSchedulerDisabled;
            $this->options->existingClones[$this->options->clone]['emailsReminderAllowed'] = empty($this->options->emailsReminderAllowed) ? false : true;
            $this->options->existingClones[$this->options->clone]['isAutoUpdatePlugins']   = empty($this->options->isAutoUpdatePlugins) ? false : true;
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
            "databaseUser"        => $this->options->databaseUser,
            "databasePassword"    => $this->options->databasePassword,
            "databaseDatabase"    => $this->options->databaseDatabase,
            "databaseServer"      => $this->options->databaseServer,
            "databasePrefix"      => $this->options->databasePrefix,
            "databaseSsl"         => (bool)$this->options->databaseSsl,
            "emailsAllowed"       => (bool) $this->options->emailsAllowed,
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

    /**
     * Get destination Hostname depending on whether WP has been installed in sub dir or not
     * @return string
     */
    private function getDestinationUrl()
    {
        if (!empty($this->options->cloneHostname)) {
            return $this->options->cloneHostname;
        }

        // if this is single site
        if (!$this->isMultisiteAndPro()) {
            return trailingslashit(get_site_url()) . $this->options->cloneDirectoryName;
        }

        // The relative path to the main multisite without appending a trailingslash e.g. wordpress
        $multisitePath = defined('PATH_CURRENT_SITE') ? PATH_CURRENT_SITE : '/';
        return rtrim($this->urls->getBaseUrl(), '/\\') . $multisitePath . $this->options->cloneDirectoryName;
    }
}
