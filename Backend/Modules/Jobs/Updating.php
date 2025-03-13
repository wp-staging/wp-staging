<?php

namespace WPStaging\Backend\Modules\Jobs;

use Exception;
use stdClass;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Database\SelectedTables;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Utils\WpDefaultDirectories;
use WPStaging\Framework\Traits\ValueGetterTrait;

/**
 * Class Updating
 * @package WPStaging\Backend\Modules\Jobs
 */
class Updating extends Job
{
    use ValueGetterTrait;

    /**
     * External Database Used
     * @var bool
     */
    public $isExternalDb;

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
     * @var Urls
     */
    private $urls;

    /**
     * Initialize is called in \Job
     */
    public function initialize()
    {
        $this->mainJob  = Job::UPDATE;
        $this->dirUtils = new WpDefaultDirectories();
        $this->sanitize = WPStaging::make(Sanitize::class);
        $this->urls     = WPStaging::make(Urls::class);
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
     * @throws Exception
     */
    public function save()
    {
        if (!isset($_POST) || !isset($_POST["cloneID"])) {
            return false;
        }

        // Delete files to copy listing
        $this->filesIndexCache->delete();

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
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'db.php',
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'object-cache.php',
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'advanced-cache.php'
        ];

        // Define mainJob to differentiate between cloning, updating and pushing
        $this->options->mainJob = $this->mainJob;

        // Only exclude wp-config.php during UPDATE not RESET
        if ($this->excludeWpConfigDuringUpdate()) {
            $this->options->excludedFilesFullPath[] = 'wp-config.php';
        }

        // Job
        $this->options->job = new stdClass();

        // Make sure it is always enabled for free version
        $this->options->emailsAllowed = true;
        // Check if clone data already exists and use that one
        if (isset($this->options->existingClones[$this->options->clone])) {
            $currentStagingSite                   = $this->options->existingClones[$this->options->clone];
            $this->options->cloneName             = $this->getValueFromArray('cloneName', $currentStagingSite);
            $this->options->cloneDirectoryName    = $this->getValueFromArray('directoryName', $currentStagingSite);
            $this->options->cloneNumber           = $this->getValueFromArray('number', $currentStagingSite);
            $this->options->databaseUser          = $this->getValueFromArray('databaseUser', $currentStagingSite);
            $this->options->databasePassword      = $this->getValueFromArray('databasePassword', $currentStagingSite);
            $this->options->databaseDatabase      = $this->getValueFromArray('databaseDatabase', $currentStagingSite);
            $this->options->databaseServer        = $this->getValueFromArray('databaseServer', $currentStagingSite);
            $this->options->databasePrefix        = $this->getValueFromArray('databasePrefix', $currentStagingSite);
            $this->options->databaseSsl           = $this->getValueFromArray('databaseSsl', $currentStagingSite);
            $this->options->destinationHostname   = $this->getValueFromArray('url', $currentStagingSite);
            $this->options->uploadsSymlinked      = $this->getValueFromArray('uploadsSymlinked', $currentStagingSite);
            $this->options->prefix                = $this->getValueFromArray('prefix', $currentStagingSite);
            $this->options->emailsAllowed         = $this->getValueFromArray('emailsAllowed', $currentStagingSite);
            $this->options->networkClone          = filter_var($this->getValueFromArray('networkClone', $currentStagingSite), FILTER_VALIDATE_BOOLEAN);
            $this->options->homeHostname          = $this->urls->getHomeUrlWithoutScheme();
            $this->options->useNewAdminAccount    = $this->getValueFromArray('useNewAdminAccount', $currentStagingSite);
            $this->options->adminEmail            = $this->getValueFromArray('adminEmail', $currentStagingSite);
            $this->options->adminPassword         = $this->getValueFromArray('adminPassword', $currentStagingSite);
            $this->options->wooSchedulerDisabled  = $this->getValueFromArray('wooSchedulerDisabled', $currentStagingSite);
            $this->options->emailsReminderAllowed = $this->getValueFromArray('emailsReminderAllowed', $currentStagingSite);
            $this->options->isAutoUpdatePlugins   = $this->getValueFromArray('isAutoUpdatePlugins', $currentStagingSite);
        } else {
            $job = 'update';
            if ($this->mainJob === Job::RESET) {
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
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'cache',
        ];

        // Add upload folder to list of excluded directories for push if symlink option is enabled
        if ($this->options->uploadsSymlinked) {
            $excludedDirectories[] = PathIdentifier::IDENTIFIER_UPLOADS;
        }

        $this->options->excludedDirectories = $excludedDirectories;

        $this->setTablesForUpdateJob();
        $this->setDirectoriesForUpdateJob();

        if (defined('WPSTGPRO_VERSION') && $this->mainJob !== Job::RESET) {
            $this->options->emailsAllowed         = isset($_POST['emailsAllowed']) && $this->sanitize->sanitizeBool($_POST['emailsAllowed']);
            $this->options->wooSchedulerDisabled  = isset($_POST['wooSchedulerDisabled']) && $this->sanitize->sanitizeBool($_POST['wooSchedulerDisabled']);
            $this->options->emailsReminderAllowed = isset($_POST['emailsReminderAllowed']) && $this->sanitize->sanitizeBool($_POST['emailsReminderAllowed']);
            $this->options->isAutoUpdatePlugins   = isset($_POST['isAutoUpdatePlugins']) && $this->sanitize->sanitizeBool($_POST['isAutoUpdatePlugins']);
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
