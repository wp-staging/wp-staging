<?php
namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\WPStaging;

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

        
        $return = array(
            "directoryName"     => $this->options->cloneDirectoryName,
            "path"              => ABSPATH . $this->options->cloneDirectoryName,
            "url"               => get_site_url() . '/' . $this->options->cloneDirectoryName,
            "number"            => $this->options->cloneNumber,
            "version"           => \WPStaging\WPStaging::VERSION,
            "status"            => false,
            "prefix"            => $this->options->prefix,
            "last_msg"          => $this->logger->getLastLogMsg(),
            "job"               => $this->options->currentJob
        );
        

        //return (object) $this->options->existingClones[$this->options->clone];
        //return (object) $this->options->existingClones[$this->clone];
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
            "url"               => get_site_url() . '/' . $this->options->cloneDirectoryName,
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

        // Save scanned directories for a delete job
        //$this->saveScannedDirectories();

        return true;
    }

    /**
     * Save Scanned Directories for Delete Job Later
     */
//    protected function saveScannedDirectories()
//    {
//        // Save scanned directories for delete job
//        $this->cache->save("delete_directories_" . $this->options->clone, $this->options->scannedDirectories);
//
//        $this->log("Successfully saved {$this->options->clone}'s clone job data to database'");
//        $this->log("Cloning job has finished!");
//    }

    /**
     * Get Upload Directory
     * @return string
     */
//    private function getUploadDirectory()
//    {
//        $wpUploadDirectory  = wp_get_upload_dir();
//        $uploadDirectory    = $wpUploadDirectory["basedir"] . DIRECTORY_SEPARATOR . WPStaging::SLUG;
//
//        // Failed to create upload directory
//        if (!is_dir($uploadDirectory) && !wp_mkdir_p($uploadDirectory))
//        {
//            $this->log("Upload directory ({$uploadDirectory}) doesn't exist and failed to create!");
//        }
//
//        $uploadDirectory = apply_filters("wpstg_get_upload_dir", $uploadDirectory);
//
//        return rtrim($uploadDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
//    }
    
    

    /**
     * Get .htaccess rules
     * @return string
     */
//    private function getHtaccessRules()
//    {
//        // Prevent directory browsing and direct access to all files
//        $rules = "<Files \"*\">\n";
//        $rules .= "<IfModule mod_access.c>\n";
//        $rules .= "Deny from all\n";
//        $rules .= "</IfModule>\n";
//        $rules .= "<IfModule !mod_access_compat>\n";
//        $rules .= "<IfModule mod_authz_host.c>\n";
//        $rules .= "Deny from all\n";
//        $rules .= "</IfModule>\n";
//        $rules .= "</IfModule>\n";
//        $rules .= "<IfModule mod_access_compat>\n";
//        $rules .= "Deny from all\n";
//        $rules .= "</IfModule>\n";
//        $rules .= "</Files>\n";
//
//        return apply_filters("wpstg_protected_directory_htaccess_rules", $rules);
//    }

    /**
     * Update .htaccess file and its rules
     * @param string $file
     * @param string $contents
     * @return bool
     */
//    private function updateHTAccess($file, $contents)
//    {
//        return (
//            (!$contents || $this->getHtaccessRules() !== $contents) &&
//            false === @file_put_contents($file, $this->getHtaccessRules())
//        );
//    }

    /**
     * Save HTAccess file
     */
//    private function saveHTAccess()
//    {
//        $uploadDir      = $this->getUploadDirectory();
//        $htAccessFile   = $uploadDir . ".htaccess";
//
//        // .htaccess exists
//        if (file_exists($htAccessFile))
//        {
//            $contents   = @file_get_contents($htAccessFile);
//
//            // Rules doesn't match, update .htaccess rules
//            if (false === $this->updateHTAccess($htAccessFile, $contents))
//            {
//                $this->log("Failed to update {$htAccessFile}");
//            }
//        }
//        // .htaccess doesn't exists and
//        else if (wp_is_writable($uploadDir) && false === @file_put_contents($htAccessFile, $this->getHtaccessRules()))
//        {
//            $this->log("Failed to create {$htAccessFile}");
//        }
//    }

    /**
     * Save blank index file
     * @return bool
     */
//    private function saveBlankIndex()
//    {
//        $uploadDir      = $this->getUploadDirectory();
//        $indexFile      = $uploadDir . "index.php";
//
//        if (file_exists($indexFile))
//        {
//            return true;
//        }
//
//        $contents = "<?php" . PHP_EOL . "// WP-Staging protection file";
//
//        if (!wp_is_writable($uploadDir) || false === @file_put_contents($indexFile, $contents))
//        {
//            $this->log("{$uploadDir} is not writable or couldn't generate {$indexFile}");
//            return false;
//        }
//
//        return true;
//    }

    /**
     * Prepare protect directories and files
     * @param bool $force
     */
//    protected function protectDirectoriesAndFiles($force = false)
//    {
//        // Don't execute
//        if (true !== get_transient("wpstg_check_protection_files") && false === $force)
//        {
//            return;
//        }
//
//        // Save .htaccess file
//        $this->saveHTAccess();
//
//        // Save blank index.php file
//        $this->saveBlankIndex();
//
//        // TODO put blank index to upload directories?? Why??
//
//        // Check files once a day
//        set_transient("wpstg_check_protection_files", true, DAY_IN_SECONDS); // 24 hours in seconds
//
//
//    }

 
}