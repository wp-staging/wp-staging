<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Core\Utils\Helper;
use WPStaging\Framework\Adapter\Database as DatabaseAdapter;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Utils\WpDefaultDirectories;

/**
 * Class Cloning
 * @package WPStaging\Backend\Modules\Jobs
 */
class Updating extends Job
{
    /**
     * @var string
     */
    const NORMAL_UPDATE = 'updating';

    /**
     * @var string
     */
    const RESET_UPDATE = 'resetting';

    /**
     * External Database Used
     * @var bool
     */
    public $isExternalDb;

    /**
     * @var mixed|null
     */
    private $db;

    /**
     * @var string
     */
    private $mainJob;

    /**
     * Initialize is called in \Job
     */
    public function initialize()
    {
        $this->db = WPStaging::getInstance()->get("wpdb");
        $this->mainJob = self::NORMAL_UPDATE;
    }

    /**
     * @param $mainJob
     */
    public function setMainJob($mainJob)
    {
        $this->mainJob = $mainJob;
    }

    /**
     * @return string
     */
    public function getMainJob()
    {
        return $this->mainJob;
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
        $this->options->clone = preg_replace("#\W+#", '-', strtolower($_POST["cloneID"]));
        $this->options->cloneDirectoryName = preg_replace("#\W+#", '-', strtolower($this->options->clone));
        $this->options->cloneNumber = 1;
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
            'object-cache.php',
            'web.config', // Important: Windows IIS configuration file. Do not copy this to the staging site is staging site is placed into subfolder
            '.wp-staging-cloneable', // File which make staging site to be cloneable
        ];

        $this->options->excludedFilesFullPath = [
            'wp-content' . DIRECTORY_SEPARATOR . 'db.php',
            'wp-content' . DIRECTORY_SEPARATOR . 'object-cache.php',
            'wp-content' . DIRECTORY_SEPARATOR . 'advanced-cache.php'
        ];

        // Define mainJob to differentiate between cloning, updating and pushing
        $this->options->mainJob = $this->mainJob;

        // Job
        $this->options->job = new \stdClass();

        // This is required for reset job because Jobs/Scan was not run for reset
        if ($this->mainJob === self::RESET_UPDATE) {
            $this->options->existingClones = get_option("wpstg_existing_clones_beta", []);
        }

        // Check if clone data already exists and use that one
        if (isset($this->options->existingClones[$this->options->clone])) {
            $this->options->cloneNumber = $this->options->existingClones[$this->options->clone]['number'];
            $this->options->databaseUser = $this->options->existingClones[$this->options->clone]['databaseUser'];
            $this->options->databasePassword = $this->options->existingClones[$this->options->clone]['databasePassword'];
            $this->options->databaseDatabase = $this->options->existingClones[$this->options->clone]['databaseDatabase'];
            $this->options->databaseServer = $this->options->existingClones[$this->options->clone]['databaseServer'];
            $this->options->databasePrefix = $this->options->existingClones[$this->options->clone]['databasePrefix'];
            $this->options->destinationHostname = $this->options->existingClones[$this->options->clone]['url'];
            $this->options->uploadsSymlinked = isset($this->options->existingClones[strtolower($this->options->clone)]['uploadsSymlinked']) ? $this->options->existingClones[strtolower($this->options->clone)]['uploadsSymlinked'] : false;
            $this->options->prefix = $this->options->existingClones[$this->options->clone]['prefix'];
            $this->options->emailsAllowed = $this->options->existingClones[$this->options->clone]['emailsAllowed'];
            //$this->options->prefix = $this->getStagingPrefix();
            $helper = new Helper();
            $this->options->homeHostname = $helper->getHomeUrlWithoutScheme();
        } else {
            $job = 'update';
            if ($this->mainJob === self::RESET_UPDATE) {
                $job = 'reset';
            }

            wp_die("Fatal Error: Can not {$job} clone because there is no clone data.");
        }

        $this->isExternalDb = !(empty($this->options->databaseUser) && empty($this->options->databasePassword));

        // Excluded Directories TOTAL
        // Do not copy these folders and plugins
        $excludedDirectories = [
            WPStaging::getWPpath() . 'wp-content' . DIRECTORY_SEPARATOR . 'cache',
            WPStaging::getWPpath() . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wps-hide-login',
            WPStaging::getWPpath() . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wp-super-cache',
            WPStaging::getWPpath() . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'peters-login-redirect',
        ];

        // Add upload folder to list of excluded directories for push if symlink option is enabled
        if ($this->options->uploadsSymlinked) {
            $wpUploadsFolder = (new WpDefaultDirectories())->getUploadsPath();
            $excludedDirectories[] = rtrim($wpUploadsFolder, '/\\');
        }

        $this->options->excludedDirectories = $excludedDirectories;

        if ($this->mainJob === self::RESET_UPDATE) {
            $this->setTablesForResetJob();
            $this->options->includedDirectories = (new WpDefaultDirectories())->getWpCoreDirectories();
            // Files
            $this->options->totalFiles    = 0;
            $this->options->totalFileSize = 0;
            $this->options->copiedFiles   = 0;
            // Job
            $this->options->currentJob  = "PreserveDataFirstStep";
            $this->options->currentStep = 0;
            $this->options->totalSteps  = 0;
        } else {
            $this->setTablesForUpdateJob();
            $this->setDirectoriesForUpdateJob();
            // Make sure it is always enabled for free version
            $this->options->emailsAllowed = true;
            if (defined('WPSTGPRO_VERSION')) {
                $this->options->emailsAllowed = isset($_POST['emailsAllowed']) && $_POST['emailsAllowed'] !== "false";
            }
        }

        $this->options->cloneDir = '';
        if (isset($_POST["cloneDir"]) && !empty($_POST["cloneDir"])) {
            $this->options->cloneDir = wpstg_urldecode(trailingslashit($_POST["cloneDir"]));
        }

        $this->options->destinationDir = $this->getDestinationDir();

        $this->options->cloneHostname = $this->options->destinationHostname;

        // Process lock state
        $this->options->isRunning = true;

        return $this->saveOptions();
    }

    /**
     * Get Destination Directory including staging subdirectory
     * @return string
     */
    private function getDestinationDir()
    {
        if (empty($this->options->cloneDir)) {
            return trailingslashit(WPStaging::getWPpath() . $this->options->cloneDirectoryName);
        }
        return trailingslashit($this->options->cloneDir);
    }

    private function setDirectoriesForUpdateJob()
    {
        $this->options->areDirectoriesIncluded = isset($_POST['areDirectoriesIncluded']) && $_POST['areDirectoriesIncluded'] === 'true';

        $directories = '';
        // Included Directories
        if ($this->options->areDirectoriesIncluded) {
            $directories = isset($_POST["includedDirectories"]) ? $_POST["includedDirectories"] : '';
        } else { // Get Included Directories from Excluded Directories
            $directories = isset($_POST["excludedDirectories"]) ? $_POST["excludedDirectories"] : '';
        }

        $this->options->includedDirectories = (new WpDefaultDirectories())->getSelectedDirectories($directories, $this->options->areDirectoriesIncluded);

        // Extra Directories
        if (isset($_POST["extraDirectories"])) {
            $this->options->extraDirectories = wpstg_urldecode(explode(Scan::DIRECTORIES_SEPARATOR, $_POST["extraDirectories"]));
        }
    }

    private function setTablesForUpdateJob()
    {
        // Included Tables
        if (isset($_POST["includedTables"]) && is_array($_POST["includedTables"])) {
            $this->options->tables = $_POST["includedTables"];
        } else {
            $this->options->tables = [];
        }

        // delete uploads folder before copying if uploads is not symlinked
        $this->options->deleteUploadsFolder = !$this->options->uploadsSymlinked && isset($_POST['cleanUploadsDir']) && $_POST['cleanUploadsDir'] === 'true';
        // should not backup uploads during update process
        $this->options->backupUploadsFolder = false;
        // clean plugins and themes dir before updating
        $this->options->deletePluginsAndThemes = isset($_POST['cleanPluginsThemes']) && $_POST['cleanPluginsThemes'] === 'true';
        // set default statuses for backup of uploads dir and cleaning of uploads, themes and plugins dirs
        $this->options->statusBackupUploadsDir = 'skipped';
        $this->options->statusContentCleaner = 'pending';
    }

    private function setTablesForResetJob()
    {
        $tableService = new TableService(new DatabaseAdapter());
        $tables = $tableService->findTableStatusStartsWith();
        $tables = $tableService->getTablesName($tables->toArray());
        $this->options->tables = $tables;
        $this->options->excludedTables = [];
    }

    /**
     * Start the cloning job
     * not used but is abstract
     */
    public function start()
    {
    }
}
