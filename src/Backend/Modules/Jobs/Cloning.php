<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\WPStaging;
use WPStaging\Backend\Modules\Jobs\Exceptions\JobNotFoundException;
use WPStaging\Backend\Modules\Jobs\Multisite\Database as muDatabase;
use WPStaging\Backend\Modules\Jobs\Multisite\DatabaseExternal as muDatabaseExternal;
use WPStaging\Backend\Modules\Jobs\Multisite\SearchReplace as muSearchReplace;
use WPStaging\Backend\Modules\Jobs\Multisite\SearchReplaceExternal as muSearchReplaceExternal;
use WPStaging\Backend\Modules\Jobs\Multisite\Data as muData;
use WPStaging\Backend\Modules\Jobs\Multisite\DataExternal as muDataExternal;
use WPStaging\Backend\Modules\Jobs\Multisite\Finish as muFinish;
use WPStaging\Backend\Modules\Jobs\Multisite\Directories as muDirectories;
use WPStaging\Backend\Modules\Jobs\Multisite\Files as muFiles;
use WPStaging\Utils\Helper;

/**
 * Class Cloning
 * @package WPStaging\Backend\Modules\Jobs
 */
class Cloning extends Job
{

    /**
     * Initialize is called in \Job
     */
    public function initialize()
    {
        $this->db = WPStaging::getInstance()->get("wpdb");
    }

    /**
     * Save Chosen Cloning Settings
     * @return bool
     */
    public function save()
    {
        if (!isset($_POST) || !isset($_POST["cloneID"])) {
            return false;
        }

        // Delete files to copy listing
        $this->cache->delete("files_to_copy");

        // Generate Options
        // Clone
        //$this->options->clone                 = $_POST["cloneID"];
        $this->options->clone = preg_replace("#\W+#", '-', strtolower($_POST["cloneID"]));
        $this->options->cloneDirectoryName = preg_replace("#\W+#", '-', strtolower($this->options->clone));
        $this->options->cloneNumber = 1;
        $this->options->prefix = $this->setStagingPrefix();
        $this->options->includedDirectories = array();
        $this->options->excludedDirectories = array();
        $this->options->extraDirectories = array();
        $this->options->excludedFiles = array(
            '.htaccess',
            '.DS_Store',
            '*.git',
            '*.svn',
            '*.tmp',
            'desktop.ini',
            '.gitignore',
            '*.log',
            'web.config', // Important: Windows IIS configuration file. Must not be in the staging site!
            '.wp-staging' // Determines if a site is a staging site
        );
        $this->options->excludedFilesFullPath = array(
            'wp-content' . DIRECTORY_SEPARATOR . 'db.php',
            'wp-content' . DIRECTORY_SEPARATOR . 'object-cache.php',
            'wp-content' . DIRECTORY_SEPARATOR . 'advanced-cache.php'
        );
        $this->options->currentStep = 0;

        // Job
        $this->options->job = new \stdClass();

        // Check if clone data already exists and use that one
        if (isset($this->options->existingClones[$this->options->clone])) {

            $this->options->cloneNumber = $this->options->existingClones[$this->options->clone]->number;

            $this->options->prefix = isset($this->options->existingClones[$this->options->clone]->prefix) ?
                $this->options->existingClones[$this->options->clone]->prefix :
                $this->setStagingPrefix();
        }
        // Clone does not exist but there are other clones in db
        // Get data and increment it
        elseif (!empty($this->options->existingClones)) {
            $this->options->cloneNumber = count($this->options->existingClones) + 1;
        }

        // Included Tables
        if (isset($_POST["includedTables"]) && is_array($_POST["includedTables"])) {
            $this->options->tables = $_POST["includedTables"];
        } else {
            $this->options->tables = array();
        }

        // Excluded Directories
        if (isset($_POST["excludedDirectories"]) && is_array($_POST["excludedDirectories"])) {
            $this->options->excludedDirectories = wpstg_urldecode($_POST["excludedDirectories"]);
        }

        // Excluded Directories TOTAL
        // Do not copy these folders and plugins
        $excludedDirectories = array(
            \WPStaging\WPStaging::getWPpath() . 'wp-content' . DIRECTORY_SEPARATOR . 'cache',
            \WPStaging\WPStaging::getWPpath() . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wps-hide-login',
            \WPStaging\WPStaging::getWPpath() . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wp-super-cache',
            \WPStaging\WPStaging::getWPpath() . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'peters-login-redirect',
            \WPStaging\WPStaging::getWPpath() . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wp-spamshield',
        );

        $this->options->excludedDirectories = array_merge($excludedDirectories, wpstg_urldecode($this->options->excludedDirectories));

        array_unshift($this->options->directoriesToCopy, \WPStaging\WPStaging::getWPpath());

        // Included Directories
        if (isset($_POST["includedDirectories"]) && is_array($_POST["includedDirectories"])) {
            $this->options->includedDirectories = wpstg_urldecode($_POST["includedDirectories"]);
        }

        // Extra Directories
        if (isset($_POST["extraDirectories"]) && !empty($_POST["extraDirectories"])) {
            $this->options->extraDirectories = wpstg_urldecode($_POST["extraDirectories"]);
        }

        // Directories to Copy
        $this->options->directoriesToCopy = array_merge(
            $this->options->includedDirectories, $this->options->extraDirectories
        );


        $this->options->databaseServer = 'localhost';
        if (isset($_POST["databaseServer"]) && !empty($_POST["databaseServer"])) {
            $this->options->databaseServer = $_POST["databaseServer"];
        }
        $this->options->databaseUser = '';
        if (isset($_POST["databaseUser"]) && !empty($_POST["databaseUser"])) {
            $this->options->databaseUser = $_POST["databaseUser"];
        }
        $this->options->databasePassword = '';
        if (isset($_POST["databasePassword"]) && !empty($_POST["databasePassword"])) {
            $this->options->databasePassword = $_POST["databasePassword"];
        }
        $this->options->databaseDatabase = '';
        if (isset($_POST["databaseDatabase"]) && !empty($_POST["databaseDatabase"])) {
            $this->options->databaseDatabase = $_POST["databaseDatabase"];
        }
        $this->options->databasePrefix = '';
        if (isset($_POST["databasePrefix"]) && !empty($_POST["databasePrefix"])) {
            $this->options->databasePrefix = strtolower($this->sanitizePrefix($_POST["databasePrefix"]));
        }
        $this->options->cloneDir = '';
        if (isset($_POST["cloneDir"]) && !empty($_POST["cloneDir"])) {
            $this->options->cloneDir = trailingslashit(wpstg_urldecode($_POST["cloneDir"]));
        }
        $this->options->cloneHostname = '';
        if (isset($_POST["cloneHostname"]) && !empty($_POST["cloneHostname"])) {
            $this->options->cloneHostname = trim($_POST["cloneHostname"]);
        }

        $this->options->destinationHostname = $this->getDestinationHostname();
        $this->options->destinationDir = $this->getDestinationDir();

        $helper = new Helper();
        $this->options->homeHostname = $helper->get_home_url_without_scheme();

        // Process lock state
        $this->options->isRunning = true;

        // Save Clone data
        $this->saveClone();

        return $this->saveOptions();
    }


    /**
     * Save clone data initially
     * @return boolean
     */
    private function saveClone()
    {
        // Save new clone data
        $this->log("Cloning: {$this->options->clone}'s clone job's data is not in database, generating data");

        $this->options->existingClones[$this->options->clone] = array(
            "directoryName" => $this->options->cloneDirectoryName,
            "path" => trailingslashit($this->options->destinationDir),
            "url" => $this->getDestinationUrl(),
            "number" => $this->options->cloneNumber,
            "version" => WPStaging::getVersion(),
            "status" => "unfinished or broken",
            "prefix" => $this->options->prefix,
            "datetime" => time(),
            "databaseUser" => $this->options->databaseUser,
            "databasePassword" => $this->options->databasePassword,
            "databaseDatabase" => $this->options->databaseDatabase,
            "databaseServer" => $this->options->databaseServer,
            "databasePrefix" => $this->options->databasePrefix
        );

        if (false === update_option("wpstg_existing_clones_beta", $this->options->existingClones)) {
            $this->log("Cloning: Failed to save {$this->options->clone}'s clone job data to database'");
            return false;
        }

        return true;
    }

    /**
     * Get destination Hostname depending on wheather WP has been installed in sub dir or not
     * @return type
     */
    private function getDestinationUrl()
    {

        if (!empty($this->options->cloneHostname)) {
            return $this->options->cloneHostname;
        }

        return trailingslashit(get_site_url()) . $this->options->cloneDirectoryName;
    }

    /**
     * Return target hostname
     * @return string
     */
    private function getDestinationHostname()
    {
        if (empty($this->options->cloneHostname)) {
            $helper = new Helper();
            return $helper->get_home_url_without_scheme();
        }
        return $this->getHostnameWithoutScheme($this->options->cloneHostname);
    }

    /**
     * Return Hostname without scheme
     * @param string $str
     * @return string
     */
    private function getHostnameWithoutScheme($string)
    {
        return preg_replace('#^https?://#', '', rtrim($string, '/'));
    }

    /**
     * Get Destination Directory including staging subdirectory
     * @return type
     */
    private function getDestinationDir()
    {
        // Throw fatal error
        if (!empty($this->options->cloneDir) & (trailingslashit($this->options->cloneDir) === ( string )trailingslashit(\WPStaging\WPStaging::getWPpath()))) {
            $this->returnException('Error: Target Directory must be different from the root of the production website.');
            die();
        }

        // No custom clone dir so clone path will be in subfolder of root
        if (empty($this->options->cloneDir)) {
            $this->options->cloneDir = trailingslashit(\WPStaging\WPStaging::getWPpath() . $this->options->cloneDirectoryName);
            return $this->options->cloneDir;
            //return trailingslashit( \WPStaging\WPStaging::getWPpath() . $this->options->cloneDirectoryName );
        }
        return trailingslashit($this->options->cloneDir);
    }

    /**
     * Make sure prefix contains appending underscore
     *
     * @param string $string
     * @return string
     */
    private function sanitizePrefix($string)
    {
        $lastCharacter = substr($string, -1);
        if ($lastCharacter === '_') {
            return $string;
        }
        return $string . '_';
    }

    /**
     * Create a new staging prefix which does not already exists in database
     */
    private function setStagingPrefix()
    {

        // Get & find a new prefix that does not already exist in database. 
        // Loop through up to 1000 different possible prefixes should be enough here;)
        for ($i = 0; $i <= 10000; $i++) {
            $this->options->prefix = isset($this->options->existingClones) ?
                'wpstg' . (count($this->options->existingClones) + $i) . '_' :
                'wpstg' . $i . '_';

            $sql = "SHOW TABLE STATUS LIKE '{$this->options->prefix}%'";
            $tables = $this->db->get_results($sql);

            // Prefix does not exist. We can use it
            if (!$tables) {
                return strtolower($this->options->prefix);
            }
        }
        $this->returnException("Fatal Error: Can not create staging prefix. '{$this->options->prefix}' already exists! Stopping for security reasons. Contact support@wp-staging.com");
        wp_die("Fatal Error: Can not create staging prefix. Prefix '{$this->options->prefix}' already exists! Stopping for security reasons. Contact support@wp-staging.com");
    }

    /**
     * Check if potential new prefix of staging site would be identical with live site.
     * @return boolean
     */
    private function isPrefixIdentical()
    {
        $db = WPStaging::getInstance()->get("wpdb");

        $livePrefix = $db->prefix;
        $stagingPrefix = $this->options->prefix;

        if ($livePrefix == $stagingPrefix) {
            return true;
        }
        return false;
    }

    /**
     * Start the cloning job
     */
    public function start()
    {
        if (!property_exists($this->options, 'currentJob') || null === $this->options->currentJob) {
            $this->log("Cloning job finished");
            return true;
        }

        $methodName = "job" . ucwords($this->options->currentJob);

        if (!method_exists($this, $methodName)) {
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
        if (true !== $response->status) {
            return $response;
        }

        $this->options->currentJob = $nextJob;
        $this->options->currentStep = 0;
        $this->options->totalSteps = 0;

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

        // Could be written more elegant
        // but for xdebug purposes and breakpoints its cleaner to have separate if blocks
        if (defined('WPSTGPRO_VERSION') && is_multisite()) {
            // Is Multisite
            if (empty($this->options->databaseUser) && empty($this->options->databasePassword)) {
                $database = new muDatabase();
            } else {
                $database = new muDatabaseExternal();
            }
        } else {

            // No Multisite
            if (empty($this->options->databaseUser) && empty($this->options->databasePassword)) {
                $database = new Database();
            } else {
                $database = new DatabaseExternal();
            }
        }
        return $this->handleJobResponse($database->start(), "SearchReplace");
    }

    /**
     * Search & Replace
     * @return object
     */
    public function jobSearchReplace()
    {
        if (defined('WPSTGPRO_VERSION') && is_multisite()) {
            if (empty($this->options->databaseUser) && empty($this->options->databasePassword)) {
                $searchReplace = new muSearchReplace();
            } else {
                $searchReplace = new muSearchReplaceExternal();
            }
        } else {
            if (empty($this->options->databaseUser) && empty($this->options->databasePassword)) {
                $searchReplace = new SearchReplace();
            } else {
                $searchReplace = new SearchReplaceExternal();
            }
        }
        return $this->handleJobResponse($searchReplace->start(), "directories");
    }

    /**
     * Get All Files From Selected Directories Recursively Into a File
     * @return object
     */
    public function jobDirectories()
    {
        if (defined('WPSTGPRO_VERSION') && is_multisite()) {
            $directories = new muDirectories();
        } else {
            $directories = new Directories();
        }
        return $this->handleJobResponse($directories->start(), "files");
    }

    /**
     * Copy Files
     * @return object
     */
    public function jobFiles()
    {
        if (defined('WPSTGPRO_VERSION') && is_multisite()) {
            $files = new muFiles();
        } else {
            $files = new Files();
        }
        return $this->handleJobResponse($files->start(), "data");
    }


    /**
     * Replace Data
     * @return object
     */
    public function jobData()
    {
        if (defined('WPSTGPRO_VERSION') && is_multisite()) {
            if (empty($this->options->databaseUser) && empty($this->options->databasePassword)) {
                $data = new muData();
            } else {
                $data = new muDataExternal();
            }
        } else {

            if (empty($this->options->databaseUser) && empty($this->options->databasePassword)) {
                $data = new Data();
            } else {
                $data = new DataExternal();
            }
        }
        return $this->handleJobResponse($data->start(), "finish");
    }

    /**
     * Save Clone Data
     * @return object
     */
    public function jobFinish()
    {
        if (defined('WPSTGPRO_VERSION') && is_multisite()) {
            $finish = new muFinish();
        } else {
            $finish = new Finish();
        }
        return $this->handleJobResponse($finish->start(), '');
    }

}
