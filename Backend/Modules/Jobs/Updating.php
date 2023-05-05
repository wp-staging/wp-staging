<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Core\Utils\Helper;
use WPStaging\Framework\Database\SelectedTables;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Utils\SlashMode;
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
     * @var WpDefaultDirectories
     */
    private $dirUtils;

    /**
     * @var Sanitize
     */
    private $sanitize;

    /**
     * Initialize is called in \Job
     */
    public function initialize()
    {
        $this->db       = WPStaging::getInstance()->get("wpdb");
        $this->mainJob  = self::NORMAL_UPDATE;
        $this->dirUtils = new WpDefaultDirectories();
        $this->sanitize = WPStaging::make(Sanitize::class);
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
        $this->options->clone               = preg_replace("#\W+#", '-', strtolower($this->sanitize->sanitizeString($_POST["cloneID"])));
        $this->options->cloneNumber         = 1;
        $this->options->includedDirectories = [];
        $this->options->excludedDirectories = [];
        $this->options->extraDirectories    = [];
        $this->options->excludeGlobRules    = [];
        $this->options->excludeSizeRules    = [];
        $this->options->excludedFiles       = [
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
            $this->dirUtils->getRelativeWpContentPath(SlashMode::TRAILING_SLASH) . 'db.php',
            $this->dirUtils->getRelativeWpContentPath(SlashMode::TRAILING_SLASH) . 'object-cache.php',
            $this->dirUtils->getRelativeWpContentPath(SlashMode::TRAILING_SLASH) . 'advanced-cache.php'
        ];

        // Define mainJob to differentiate between cloning, updating and pushing
        $this->options->mainJob = $this->mainJob;

        // Only exclude wp-config.php during UPDATE not RESET
        if ($this->excludeWpConfigDuringUpdate()) {
            $this->options->excludedFilesFullPath[] = 'wp-config.php';
        }

        // Job
        $this->options->job = new \stdClass();

        // Check if clone data already exists and use that one
        if (isset($this->options->existingClones[$this->options->clone])) {
            $this->options->cloneName           = $this->options->existingClones[$this->options->clone]['cloneName'];
            $this->options->cloneDirectoryName  = $this->options->existingClones[$this->options->clone]['directoryName'];
            $this->options->cloneNumber         = $this->options->existingClones[$this->options->clone]['number'];
            $this->options->databaseUser        = $this->options->existingClones[$this->options->clone]['databaseUser'];
            $this->options->databasePassword    = $this->options->existingClones[$this->options->clone]['databasePassword'];
            $this->options->databaseDatabase    = $this->options->existingClones[$this->options->clone]['databaseDatabase'];
            $this->options->databaseServer      = $this->options->existingClones[$this->options->clone]['databaseServer'];
            $this->options->databasePrefix      = $this->options->existingClones[$this->options->clone]['databasePrefix'];
            $this->options->databaseSsl         = $this->options->existingClones[$this->options->clone]['databaseSsl'];
            $this->options->destinationHostname = $this->options->existingClones[$this->options->clone]['url'];
            $this->options->uploadsSymlinked    = isset($this->options->existingClones[strtolower($this->options->clone)]['uploadsSymlinked']) ? $this->options->existingClones[strtolower($this->options->clone)]['uploadsSymlinked'] : false;
            $this->options->prefix              = $this->options->existingClones[$this->options->clone]['prefix'];
            $this->options->emailsAllowed       = $this->options->existingClones[$this->options->clone]['emailsAllowed'];
            $this->options->networkClone        = isset($this->options->existingClones[strtolower($this->options->clone)]['networkClone']) ? $this->options->existingClones[$this->options->clone]['networkClone'] : false;
            $this->options->networkClone        = filter_var($this->options->networkClone, FILTER_VALIDATE_BOOLEAN);
            //$this->options->prefix = $this->getStagingPrefix();
            $helper                      = new Helper();
            $this->options->homeHostname = $helper->getHomeUrlWithoutScheme();
        } else {
            $job = 'update';
            if ($this->mainJob === self::RESET_UPDATE) {
                $job = 'reset';
            }

            wp_die(sprintf("Fatal Error: Can not %s clone because there is no clone data.", esc_html($job)));
        }

        $this->isExternalDb = !(empty($this->options->databaseUser) && empty($this->options->databasePassword));

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

        $this->options->excludedDirectories = $excludedDirectories;

        $this->setTablesForUpdateJob();
        $this->setDirectoriesForUpdateJob();

        // Make sure it is always enabled for free version
        $this->options->emailsAllowed = true;
        if (defined('WPSTGPRO_VERSION')) {
            $this->options->emailsAllowed = isset($_POST['emailsAllowed']) && $this->sanitize->sanitizeBool($_POST['emailsAllowed']);
        }

        $this->options->cloneDir       = $this->options->existingClones[$this->options->clone]['path'];
        $this->options->destinationDir = $this->getDestinationDir();
        $this->options->cloneHostname  = $this->options->destinationHostname;

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
        // Exclude Glob Rules
        $this->options->excludeGlobRules = [];
        if (!empty($_POST["excludeGlobRules"])) {
            $this->options->excludeGlobRules = $this->sanitize->sanitizeExcludeRules($_POST["excludeGlobRules"]);
        }

        $this->options->excludeSizeRules = [];
        if (!empty($_POST["excludeSizeRules"])) {
            $this->options->excludeSizeRules = $this->sanitize->sanitizeExcludeRules($_POST["excludeSizeRules"]);
        }

        // Excluded Directories
        $excludedDirectoriesRequest         = isset($_POST["excludedDirectories"]) ? $this->sanitize->sanitizeString($_POST["excludedDirectories"]) : '';
        $excludedDirectoriesRequest         = $this->dirUtils->getExcludedDirectories($excludedDirectoriesRequest);
        $this->options->excludedDirectories = array_merge($this->options->excludedDirectories, $excludedDirectoriesRequest);
        // Extra Directories
        if (isset($_POST["extraDirectories"])) {
            $this->options->extraDirectories = explode(ScanConst::DIRECTORIES_SEPARATOR, $this->sanitize->sanitizeString($_POST["extraDirectories"]));
        }

        // delete uploads folder before copying if uploads is not symlinked
        $this->options->deleteUploadsFolder = !$this->options->uploadsSymlinked && isset($_POST['cleanUploadsDir']) && $this->sanitize->sanitizeBool($_POST['cleanUploadsDir']);
        // should not backup uploads during update process
        $this->options->backupUploadsFolder = false;
        // clean plugins and themes dir before updating
        $this->options->deletePluginsAndThemes = isset($_POST['cleanPluginsThemes']) && $this->sanitize->sanitizeBool($_POST['cleanPluginsThemes']);
        // set default statuses for backup of uploads dir and cleaning of uploads, themes and plugins dirs
        $this->options->statusBackupUploadsDir = 'skipped';
        $this->options->statusContentCleaner   = 'pending';
    }

    private function setTablesForUpdateJob()
    {
        // Included Tables / Prefixed Table - Excluded Tables
        $includedTables              = isset($_POST['includedTables']) ? $this->sanitize->sanitizeString($_POST['includedTables']) : '';
        $excludedTables              = isset($_POST['excludedTables']) ? $this->sanitize->sanitizeString($_POST['excludedTables']) : '';
        $selectedTablesWithoutPrefix = isset($_POST['selectedTablesWithoutPrefix']) ? $this->sanitize->sanitizeString($_POST['selectedTablesWithoutPrefix']) : '';
        $selectedTables              = new SelectedTables($includedTables, $excludedTables, $selectedTablesWithoutPrefix);
        $selectedTables->setAllTablesExcluded(empty($_POST['allTablesExcluded']) ? false : $this->sanitize->sanitizeBool($_POST['allTablesExcluded']));
        $this->options->tables = $selectedTables->getSelectedTables($this->options->networkClone);
    }

    /**
     * Start the cloning job
     * not used but is abstract
     */
    public function start()
    {
    }
}
