<?php
namespace WPStaging\Backend\Modules\Jobs\Multisite;

use WPStaging\WPStaging;
use WPStaging\Backend\Modules\Jobs\Job;
use WPStaging\Utils\Multisite;

//error_reporting( E_ALL );

/**
 * Class Finish
 * @package WPStaging\Backend\Modules\Jobs
 */
class Finish extends Job
{
    /**
     * Clone Key
     * @var string 
     */
    private $clone = '';
    
    

    /**
     * Start Module
     * @return object
     */
    public function start()
    {
        // sanitize the clone name before saving
        $this->clone = preg_replace("#\W+#", '-', strtolower($this->options->clone)); 
        
        // Delete Cache Files
        $this->deleteCacheFiles();

        // Prepare clone records & save scanned directories for delete job later
        $this->prepareCloneDataRecords();

        $multisite = new Multisite;
        
        $return = array(
            "directoryName"     => $this->options->cloneDirectoryName,
            "path"              => ABSPATH . $this->options->cloneDirectoryName,
            //"url"               => get_site_url() . '/' . $this->options->cloneDirectoryName,
            "url"               => $this->multisiteHomeUrl . '/' . $this->options->cloneDirectoryName,
            "number"            => $this->options->cloneNumber,
            "version"           => \WPStaging\WPStaging::VERSION,
            "status"            => 'finished',
            "prefix"            => $this->options->prefix,
            "last_msg"          => $this->logger->getLastLogMsg(),
            "job"               => $this->options->currentJob,
            "percentage"        => 100
                
        );
        
        //$this->flush();
        
        return (object) $return;
    }

    /**
     * Delete Cache Files
     */
    protected function deleteCacheFiles()
    {
        $this->log("Finish: Deleting clone job's cache files...");

        // Clean cache files
        $this->cache->delete("clone_options");
        $this->cache->delete("files_to_copy");

        $this->log("Finish: Clone job's cache files have been deleted!");
    }
    
    /**
     * Prepare clone records
     * @return bool
     */
    protected function prepareCloneDataRecords()
    {
        // Check if clones still exist
        $this->log("Finish: Verifying existing clones...");

        // Clone data already exists
        if (isset($this->options->existingClones[$this->options->clone]))
        {
            $this->log("Finish: Clone data already exists, no need to update, the job finished");
            return true;
        }

        // Save new clone data
        $this->log("Finish: {$this->options->clone}'s clone job's data is not in database, generating data");
        
        // sanitize the clone name before saving
        //$clone = preg_replace("#\W+#", '-', strtolower($this->options->clone));
        
        $this->options->existingClones[$this->clone] = array(
            "directoryName"     => $this->options->cloneDirectoryName,
            "path"              => ABSPATH . $this->options->cloneDirectoryName,
            //"url"               => get_site_url() . '/' . $this->options->cloneDirectoryName,
            "url"               => $this->multisiteHomeUrl . '/' . $this->options->cloneDirectoryName,
            "number"            => $this->options->cloneNumber,
            "version"           => \WPStaging\WPStaging::VERSION,
            "status"            => false,
            "prefix"            => $this->options->prefix,
        );

        if (false === update_option("wpstg_existing_clones_beta", $this->options->existingClones))
        {
            $this->log("Finish: Failed to save {$this->options->clone}'s clone job data to database'");
            return false;
        }

        return true;
    }
}