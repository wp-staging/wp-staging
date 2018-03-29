<?php
namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Backend\Modules\Jobs\Exceptions\JobNotFoundException;
use WPStaging\WPStaging;

/**
 * Class Cloning
 * @package WPStaging\Backend\Modules\Jobs
 */
class Cloning extends Job
{
    /**
     * Initialize is called in \Job
     */
    public function initialize(){
        $this->db                   = WPStaging::getInstance()->get("wpdb");
    }

    /**
     * Save Chosen Cloning Settings
     * @return bool
     */
    public function save()
    {
        if (!isset($_POST) || !isset($_POST["cloneID"]))
        {
            return false;
        }

        // Generate Options
        // Clone
        $this->options->clone               = $_POST["cloneID"];
        $this->options->cloneDirectoryName  = preg_replace("#\W+#", '-', strtolower($this->options->clone));
        $this->options->cloneNumber         = 1;
        $this->options->prefix = $this->setStagingPrefix();
        $this->options->includedDirectories = array();
        $this->options->excludedDirectories = array();
        $this->options->extraDirectories    = array();
        $this->options->excludedFiles       = array('.htaccess', '.DS_Store', '.git', '.svn', '.tmp', 'desktop.ini', '.gitignore', '.log');
        $this->options->currentStep         = 0;

        // Job
        $this->options->job                 = new \stdClass();

        // Check if clone data already exists and use that one
        if (isset($this->options->existingClones[$this->options->clone]) )
        {
            
            $this->options->cloneNumber = $this->options->existingClones[$this->options->clone]->number;
            
            $this->options->prefix = isset($this->options->existingClones[$this->options->clone]->prefix) ? 
                    $this->options->existingClones[$this->options->clone]->prefix : 
                    $this->setStagingPrefix();  
            
        }   // Clone does not exist but there are other clones in db
            // Get data and increment it
        elseif (!empty($this->options->existingClones))
        {
            $this->options->cloneNumber =  count($this->options->existingClones)+1;
            $this->options->prefix = $this->setStagingPrefix();  
        }

        // Included Tables
        if (isset($_POST["includedTables"]) && is_array($_POST["includedTables"]))
        {
            $this->options->tables = $_POST["includedTables"];
        } else {
            $this->options->tables = array(); 
        }
        // Excluded Tables
//        if (isset($_POST["excludedTables"]) && is_array($_POST["excludedTables"]))
//        {
//            $this->options->excludedTables = $_POST["excludedTables"];
//        }

        // Excluded Directories
        if (isset($_POST["excludedDirectories"]) && is_array($_POST["excludedDirectories"]))
        {
            $this->options->excludedDirectories = $_POST["excludedDirectories"];
        }
        
        
        // Excluded Directories TOTAL
        // Do not copy these folders and plugins
        $excludedDirectories = array(
            ABSPATH . 'wp-content' . DIRECTORY_SEPARATOR . 'cache',
            ABSPATH . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wps-hide-login',
            ABSPATH . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wp-super-cache',
            ABSPATH . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'peters-login-redirect',
            );
        
        $this->options->excludedDirectories = array_merge($excludedDirectories, $this->options->excludedDirectories);

        // Included Directories
        if (isset($_POST["includedDirectories"]) && is_array($_POST["includedDirectories"]))
        {
            $this->options->includedDirectories = $_POST["includedDirectories"];
        }

        // Extra Directories
        if (isset($_POST["extraDirectories"]) && !empty($_POST["extraDirectories"]) )
        {      
            $this->options->extraDirectories = $_POST["extraDirectories"];
        }

        // Directories to Copy
        $this->options->directoriesToCopy = array_merge(
            $this->options->includedDirectories,
            $this->options->extraDirectories
        );

        array_unshift($this->options->directoriesToCopy, ABSPATH);
                
        // Delete files to copy listing
        $this->cache->delete("files_to_copy");

        return $this->saveOptions();
    }

    /**
     * Create a new staging prefix which does not already exists in database
     */
    public function setStagingPrefix(){
        
        // Get & find a new prefix that does not already exist in database. 
        // Loop through up to 1000 different possible prefixes should be enough here;)
        for($i=0; $i <= 10000; $i++){
            $this->options->prefix = isset($this->options->existingClones) ? 
                    'wpstg' . (count($this->options->existingClones)+$i) . '_' : 
                    'wpstg' . $i . '_';
            
            $sql = "SHOW TABLE STATUS LIKE '{$this->options->prefix}%'";
            $tables = $this->db->get_results($sql);
            
            // Prefix does not exists. We can use it
            if (!$tables){
                //$this->returnException('new ' . $this->options->prefix);
                return $this->options->prefix;
            }
        }
        $this->returnException("Fatal Error: Can not create staging prefix. '{$this->options->prefix}' already exists! Stopping for security reasons. Contact support@wp-staging.com");
        wp_die("Fatal Error: Can not create staging prefix. Prefix '{$this->options->prefix}' already exists! Stopping for security reasons. Contact support@wp-staging.com");
        }
    
    /**
     * Check if potential new prefix of staging site would be identical with live site. 
     * @return boolean
     */
    private function isPrefixIdentical(){
        $db = WPStaging::getInstance()->get("wpdb");
        
        $livePrefix = $db->prefix;
        $stagingPrefix = $this->options->prefix;
        
        if ($livePrefix == $stagingPrefix){
            return true;
        }
        return false;
    }

    /**
     * Start the cloning job
     */
    public function start()
    {
        if (null === $this->options->currentJob)
        {
            $this->log("Cloning job for {$this->options->clone} finished");
            return true;
        }

        $methodName = "job" . ucwords($this->options->currentJob);

        if (!method_exists($this, $methodName))
        {
            $this->log("Can't execute job; Job's method {$methodName} is not found");
            throw new JobNotFoundException($methodName);
        }

        // Call the job
        //$this->log("execute job: Job's method {$methodName}");
        return $this->{$methodName}();
    }

    /**
     * @param object $response
     * @param string $nextJob
     * @return object
     */
    private function handleJobResponse($response, $nextJob)
    {
        // Job is not done
        if (true !== $response->status)
        {
            return $response;
        }

        $this->options->currentJob              = $nextJob;
        $this->options->currentStep             = 0;
        $this->options->totalSteps              = 0;

        // Save options
        $this->saveOptions();

        return $response;
    }


    
       
    
    /**
     * Clone Database
     * @return object
     */
    public function jobDatabase()
    {
        $database = new Database();
        return $this->handleJobResponse($database->start(), "SearchReplace");
    }

    /**
     * Search & Replace
     * @return object
     */
    public function jobSearchReplace()
    {
        $searchReplace = new SearchReplace();
        return $this->handleJobResponse($searchReplace->start(), "directories");
    }

    /**
     * Get All Files From Selected Directories Recursively Into a File
     * @return object
     */
    public function jobDirectories()
    {
        $directories = new Directories();
        return $this->handleJobResponse($directories->start(), "files");
    }

    /**
     * Copy Files
     * @return object
     */
    public function jobFiles()
    {
        $files = new Files();
        return $this->handleJobResponse($files->start(), "data");
    }

    /**
     * Replace Data
     * @return object
     */
    public function jobData()
    {
        $data = new Data();
        return $this->handleJobResponse($data->start(), "finish");
    }

    
    /**
     * Save Clone Data
     * @return object
     */
    public function jobFinish()
    {
        $finish = new Finish();
        return $this->handleJobResponse($finish->start(), '');
    }
    
    
}