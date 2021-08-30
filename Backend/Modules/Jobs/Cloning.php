<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Backend\Modules\Jobs\Exceptions\JobNotFoundException;
use WPStaging\Core\Utils\Helper;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\Staging\Sites;
use WPStaging\Framework\Utils\SlashMode;
use WPStaging\Framework\Utils\WpDefaultDirectories;

/**
 * Class Cloning
 * @package WPStaging\Backend\Modules\Jobs
 */
class Cloning extends Job
{

    /**
     * @var object
     */
    private $db;

    /**
     * @var WpDefaultDirectories
     */
    private $dirUtils;

    /**
     * Initialize is called in \Job
     */
    public function initialize()
    {
        $this->db = WPStaging::getInstance()->get("wpdb");
        $this->dirUtils = new WpDefaultDirectories();
    }


    /**
     * Save Chosen Cloning Settings
     * @return bool
     * @throws \Exception
     */
    public function save()
    {
        if (!isset($_POST) || !isset($_POST["cloneID"])) {
            return false;
        }

        // Delete files to copy listing
        $this->cache->delete("files_to_copy");

        // Generate Options
        // Clone ID -> timestamp (time at which this clone creation initiated)
        $this->options->clone = preg_replace("#\W+#", '-', strtolower($_POST["cloneID"]));
        // Clone Name -> Site name that user input, if user left it empty it will be Clone ID 
        $this->options->cloneName = wpstg_urldecode($_POST["cloneName"]);
        // The slugified version of Clone Name (to use in directory creation)
        $this->options->cloneDirectoryName = preg_replace("#\W+#", '-', strtolower($this->options->cloneName));
        $this->options->cloneNumber = 1;
        $this->options->prefix = $this->setStagingPrefix();
        $this->options->includedDirectories = [];
        $this->options->excludedDirectories = [];
        $this->options->extraDirectories = [];
        $this->options->excludedFiles = [
            '.htaccess',
            '.DS_Store',
            '*.git',
            '*.svn',
            '*.tmp',
            'desktop.ini',
            '.gitignore',
            '*.log',
            'web.config', // Important: Windows IIS configuration file. Must not be in the staging site!
            '.wp-staging', // Determines if a site is a staging site
            '.wp-staging-cloneable', // File which make staging site to be cloneable
        ];

        $this->options->excludedFilesFullPath = [
            $this->dirUtils->getRelativeWpContentPath(SlashMode::TRAILING_SLASH) . 'db.php',
            $this->dirUtils->getRelativeWpContentPath(SlashMode::TRAILING_SLASH) . 'object-cache.php',
            $this->dirUtils->getRelativeWpContentPath(SlashMode::TRAILING_SLASH) . 'advanced-cache.php'
        ];
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
            $this->options->tables = [];
        }

        // Exclude File Size Rules
        $this->options->excludeSizeRules = [];
        if (isset($_POST["excludeSizeRules"]) && !empty($_POST["excludeSizeRules"])) {
            $this->options->excludeSizeRules = explode(',', wpstg_urldecode($_POST["excludeSizeRules"]));
        }

        // Exclude Glob Rules
        $this->options->excludeGlobRules = [];
        if (isset($_POST["excludeGlobRules"]) && !empty($_POST["excludeGlobRules"])) {
            $this->options->excludeGlobRules = explode(',', wpstg_urldecode($_POST["excludeGlobRules"]));
        }

        $this->options->uploadsSymlinked = isset($_POST['uploadsSymlinked']) && $_POST['uploadsSymlinked'] === 'true';

        /**
         * @see /WPStaging/Framework/CloningProcess/ExcludedPlugins.php to exclude plugins
         * Only add other directories here
         */
        $excludedDirectories = [
            $this->dirUtils->getRelativeWpContentPath(SlashMode::BOTH_SLASHES) . 'cache',
        ];

        // Add upload folder to list of excluded directories for push if symlink option is enabled
        if ($this->options->uploadsSymlinked) {
            $excludedDirectories[] = $this->dirUtils->getRelativeUploadPath(SlashMode::LEADING_SLASH);
        }

        $excludedDirectoriesRequest = isset($_POST["excludedDirectories"]) ? $_POST["excludedDirectories"] : '';
        $excludedDirectoriesRequest = $this->dirUtils->getExcludedDirectories($excludedDirectoriesRequest);

        $this->options->excludedDirectories = array_merge($excludedDirectories, $excludedDirectoriesRequest);

        // Extra Directories
        if (isset($_POST["extraDirectories"])) {
            $this->options->extraDirectories = explode(ScanConst::DIRECTORIES_SEPARATOR, wpstg_urldecode($_POST["extraDirectories"]));
        }

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
            $this->options->databasePassword = stripslashes($_POST["databasePassword"]);
        }
        $this->options->databaseDatabase = '';
        if (isset($_POST["databaseDatabase"]) && !empty($_POST["databaseDatabase"])) {
            $this->options->databaseDatabase = $_POST["databaseDatabase"];
        }
        $this->options->databasePrefix = '';
        if (isset($_POST["databasePrefix"]) && !empty($_POST["databasePrefix"])) {
            $this->options->databasePrefix = $this->appendUnderscore($_POST["databasePrefix"]);
        }
        $this->options->cloneDir = '';
        if (isset($_POST["cloneDir"]) && !empty($_POST["cloneDir"])) {
            $this->options->cloneDir = trailingslashit(wpstg_urldecode($_POST["cloneDir"]));
        }
        $this->options->cloneHostname = '';
        if (isset($_POST["cloneHostname"]) && !empty($_POST["cloneHostname"])) {
            $this->options->cloneHostname = trim($_POST["cloneHostname"]);
        }

        // Make sure it is always enabled for free version
        $this->options->emailsAllowed = true;
        if (defined('WPSTGPRO_VERSION')) {
            $this->options->emailsAllowed = apply_filters(
                'wpstg_cloning_email_allowed',
                isset($_POST['emailsAllowed']) && $_POST['emailsAllowed'] !== "false"
            );
        }

        $this->options->destinationHostname = $this->getDestinationHostname();
        $this->options->destinationDir = $this->getDestinationDir();

        $helper = new Helper();
        $this->options->homeHostname = $helper->getHomeUrlWithoutScheme();

        // Process lock state
        $this->options->isRunning = true;

        // id of the user creating the clone
        $this->options->ownerId = get_current_user_id();

        // Save Clone data
        $this->saveClone();

        return $this->saveOptions();
    }

    /**
     * @return bool
     */
    private function enteredDatabaseSameAsLiveDatabase()
    {
        return $this->options->databaseServer === DB_HOST && $this->options->databaseDatabase === DB_NAME;
    }

    /**
     * Save clone data initially
     * @return boolean
     */
    private function saveClone()
    {
        // Save new clone data
        $this->log("Cloning: {$this->options->clone}'s clone job's data is not in database, generating data");

        $this->options->existingClones[$this->options->clone] = [
            "cloneName" => $this->options->cloneName,
            "directoryName" => $this->options->cloneDirectoryName,
            "path" => trailingslashit($this->options->destinationDir),
            "url" => $this->getDestinationUrl(),
            "number" => $this->options->cloneNumber,
            "version" => WPStaging::getVersion(),
            "status" => "unfinished or broken (?)",
            "prefix" => $this->options->prefix,
            "datetime" => time(),
            "databaseUser" => $this->options->databaseUser,
            "databasePassword" => $this->options->databasePassword,
            "databaseDatabase" => $this->options->databaseDatabase,
            "databaseServer" => $this->options->databaseServer,
            "databasePrefix" => $this->options->databasePrefix,
            "emailsAllowed"   => (bool)$this->options->emailsAllowed,
            "uploadsSymlinked" => (bool)$this->options->uploadsSymlinked,
            "ownerId" => $this->options->ownerId,
            "includedTables"        => $this->options->tables,
            "excludeSizeRules"      => $this->options->excludeSizeRules,
            "excludeGlobRules"      => $this->options->excludeGlobRules,
            "excludedDirectories"   => $this->options->excludedDirectories,
            "extraDirectories"      => $this->options->extraDirectories,
        ];

        if (update_option(Sites::STAGING_SITES_OPTION, $this->options->existingClones) === false) {
            $this->log("Cloning: Failed to save {$this->options->clone}'s clone job data to database'");
            return false;
        }

        return true;
    }

    /**
     * Get destination Hostname depending on wheather WP has been installed in sub dir or not
     * @return string
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
            return $helper->getHomeUrlWithoutScheme();
        }
        return $this->getHostnameWithoutScheme($this->options->cloneHostname);
    }

    /**
     * Return Hostname without scheme
     * @param string $string
     * @return string
     */
    private function getHostnameWithoutScheme($string)
    {
        return preg_replace('#^https?://#', '', rtrim($string, '/'));
    }

    /**
     * Get Destination Directory including staging subdirectory
     * @return string
     */
    private function getDestinationDir()
    {
        // Throw fatal error
        if (!empty($this->options->cloneDir) & (trailingslashit($this->options->cloneDir) === (string)trailingslashit(WPStaging::getWPpath()))) {
            $this->returnException('Error: Target Directory must be different from the root of the production website.');
            die();
        }

        // No custom clone dir so clone path will be in subfolder of root
        if (empty($this->options->cloneDir)) {
            $this->options->cloneDir = trailingslashit(WPStaging::getWPpath() . $this->options->cloneDirectoryName);
            return $this->options->cloneDir;
        }
        return trailingslashit($this->options->cloneDir);
    }

    /**
     * Make sure prefix ends with underscore
     *
     * @param string $string
     * @return string
     */
    private function appendUnderscore($string)
    {
        $lastCharacter = substr($string, -1);
        if ($lastCharacter === '_') {
            return $string;
        }
        return $string . '_';
    }

    /**
     * Create a new staging prefix that does not already exists in database
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
                return $this->options->prefix;
            }
        }
        $this->returnException("Fatal Error: Can not create staging prefix. '{$this->options->prefix}' already exists! Stopping for security reasons. Contact support@wp-staging.com");
        wp_die("Fatal Error: Can not create staging prefix. Prefix '{$this->options->prefix}' already exists! Stopping for security reasons. Contact support@wp-staging.com");
    }


    /**
     * Start the cloning job
     * @throws JobNotFoundException
     */
    public function start()
    {
        if (!property_exists($this->options, 'currentJob') || $this->options->currentJob === null) {
            $this->log("Cloning job finished");
            return true;
        }

        $methodName = "job" . ucwords($this->options->currentJob);

        if (!method_exists($this, $methodName)) {
            $this->log("Can't execute job; Job's method {$methodName} is not found");
            throw new JobNotFoundException($methodName);
        }

        if ($this->options->databasePrefix === $this->db->prefix && $this->enteredDatabaseSameAsLiveDatabase()) {
            $this->returnException('Entered table prefix for staging and production database can not be identical! Please start over and change the table prefix.');
        }

        // Call the job
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
        if ($response->status !== true) {
            return $response;
        }

        $this->options->job = new \stdClass();
        $this->options->currentJob = $nextJob;
        $this->options->currentStep = 0;
        $this->options->totalSteps = 0;

        // Save options
        $this->saveOptions();

        return $response;
    }

    /**
     * Copy data from staging site to temporary column to use it later
     * @return object
     */
    public function jobPreserveDataFirstStep()
    {
        $preserve = new PreserveDataFirstStep();
        return $this->handleJobResponse($preserve->start(), 'database');
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
        return $this->handleJobResponse($searchReplace->start(), "PreserveDataSecondStep");
    }

    /**
     * Copy tmp data back to staging site
     * @return object
     */
    public function jobPreserveDataSecondStep()
    {
        $preserve = new PreserveDataSecondStep();
        return $this->handleJobResponse($preserve->start(), 'directories');
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
        return $this->handleJobResponse((new Data())->start(), "finish");
    }

    /**
     * Save Clone Data
     * @return object
     */
    public function jobFinish()
    {
        // Re-generate the token when the Clone is complete.
        // Todo: Consider adding a do_action() on jobFinish to hook here.
        // Todo: Inject using DI
        $accessToken = new AccessToken();
        $accessToken->generateNewToken();

        $finish = new Finish();
        return $this->handleJobResponse($finish->start(), '');
    }
}
