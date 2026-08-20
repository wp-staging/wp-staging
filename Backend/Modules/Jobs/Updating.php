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





class Updating extends Job
{
    use ValueGetterTrait;





    public $isExternalDb;




    private $mainJob;




    private $dirUtils;




    private $sanitize;




    private $urls;




    public function initialize()
    {
        $this->mainJob  = Job::UPDATE;
        $this->dirUtils = new WpDefaultDirectories();
        $this->sanitize = WPStaging::make(Sanitize::class);
        $this->urls     = WPStaging::make(Urls::class);
    }




    public function setMainJob($mainJob)
    {
        $this->mainJob = $mainJob;
    }




    public function getMainJob()
    {
        return $this->mainJob;
    }






    public function save()
    {
        if (!isset($_POST) || !isset($_POST["cloneID"])) {
            return false;
        }

 
        $this->filesIndexCache->delete();

 
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
            'web.config', 
            '.wp-staging-cloneable', 
        ];

        $this->options->excludedFilesFullPath = [
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'db.php',
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'object-cache.php',
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'advanced-cache.php',
        ];

 
        $this->options->mainJob = $this->mainJob;

 
        if ($this->excludeWpConfigDuringUpdate()) {
            $this->options->excludedFilesFullPath[] = 'wp-config.php';
        }

 
        $this->options->job = new stdClass();
        $this->loadLegacyExistingClones();

 
        $this->options->isEmailsAllowed = true;
 
        if (isset($this->options->existingClones[$this->options->clone])) {
            $currentStagingSite                     = $this->options->existingClones[$this->options->clone];
            $this->options->current                 = $this->options->clone;
            $this->options->currentClone            = $currentStagingSite;
            $this->options->cloneName               = $this->getValueFromArray('cloneName', $currentStagingSite);
            $this->options->cloneDirectoryName      = $this->getValueFromArray('directoryName', $currentStagingSite);
            $this->options->cloneNumber             = $this->getValueFromArray('number', $currentStagingSite);
            $this->options->databaseUser            = $this->getValueFromArray('databaseUser', $currentStagingSite);
            $this->options->databasePassword        = $this->getValueFromArray('databasePassword', $currentStagingSite);
            $this->options->databaseDatabase        = $this->getValueFromArray('databaseDatabase', $currentStagingSite);
            $this->options->databaseServer          = $this->getValueFromArray('databaseServer', $currentStagingSite);
            $this->options->databasePrefix          = $this->getValueFromArray('databasePrefix', $currentStagingSite);
            $this->options->databaseSsl             = $this->getValueFromArray('databaseSsl', $currentStagingSite);
            $this->options->useCustomDatabase       = $this->externalDatabaseConfiguration->isEnabled($currentStagingSite);
            $this->options->destinationHostname     = $this->getValueFromArray('url', $currentStagingSite);
            $this->options->uploadsSymlinked        = $this->getValueFromArray('uploadsSymlinked', $currentStagingSite);
            $this->options->prefix                  = $this->getValueFromArray('prefix', $currentStagingSite);
            $this->options->isEmailsAllowed         = $this->getValueFromArray('isEmailsAllowed', $currentStagingSite);
            $this->options->networkClone            = filter_var($this->getValueFromArray('networkClone', $currentStagingSite), FILTER_VALIDATE_BOOLEAN);
            $this->options->homeHostname            = $this->urls->getHomeUrlWithoutScheme();
            $this->options->useNewAdminAccount      = $this->getValueFromArray('useNewAdminAccount', $currentStagingSite);
            $this->options->adminEmail              = $this->getValueFromArray('adminEmail', $currentStagingSite);
            $this->options->adminPassword           = $this->getValueFromArray('adminPassword', $currentStagingSite);
            $this->options->isWooSchedulerEnabled   = $this->getValueFromArray('isWooSchedulerEnabled', $currentStagingSite);
            $this->options->isEmailsReminderEnabled = $this->getValueFromArray('isEmailsReminderEnabled', $currentStagingSite);
            $this->options->isAutoUpdatePlugins     = $this->getValueFromArray('isAutoUpdatePlugins', $currentStagingSite);
        } else {
            $job = 'update';
            if ($this->mainJob === Job::RESET) {
                $job = 'reset';
            }

            wp_die(sprintf("Fatal Error: Can not %s clone because there is no clone data.", esc_html($job)));
        }

        if (!$this->options->useCustomDatabase) {
            $this->options->databaseUser     = '';
            $this->options->databasePassword = '';
            $this->options->databaseDatabase = '';
            $this->options->databaseServer   = 'localhost';
            $this->options->databasePrefix   = '';
            $this->options->databaseSsl      = false;
        }

        $this->isExternalDb = (bool)$this->options->useCustomDatabase;





        $excludedDirectories = [
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'cache',
        ];

 
        if ($this->options->uploadsSymlinked) {
            $excludedDirectories[] = PathIdentifier::IDENTIFIER_UPLOADS;
        }

        $this->options->excludedDirectories = $excludedDirectories;

        $this->setTablesForUpdateJob();
        $this->setDirectoriesForUpdateJob();

        if (defined('WPSTGPRO_VERSION') && $this->mainJob !== Job::RESET) {
            $this->options->isEmailsAllowed         = isset($_POST['isEmailsAllowed']) && $this->sanitize->sanitizeBool($_POST['isEmailsAllowed']);
            $this->options->isWooSchedulerEnabled   = isset($_POST['isWooSchedulerEnabled']) && $this->sanitize->sanitizeBool($_POST['isWooSchedulerEnabled']);
            $this->options->isEmailsReminderEnabled = isset($_POST['isEmailsReminderEnabled']) && $this->sanitize->sanitizeBool($_POST['isEmailsReminderEnabled']);
            $this->options->isAutoUpdatePlugins     = isset($_POST['isAutoUpdatePlugins']) && $this->sanitize->sanitizeBool($_POST['isAutoUpdatePlugins']);
        }

        $this->options->cloneDir       = $this->options->existingClones[$this->options->clone]['path'];
        $this->options->destinationDir = $this->getDestinationDir();
        $this->options->cloneHostname  = $this->options->destinationHostname;

 
        $this->options->isRunning = true;
        $this->initializeLegacyStagingRun($this->mainJob);

        return $this->saveOptions();
    }





    private function getDestinationDir()
    {
        if (empty($this->options->cloneDir)) {
            return trailingslashit(WPStaging::getWPpath() . $this->options->cloneDirectoryName);
        }

        return trailingslashit($this->options->cloneDir);
    }

    private function setDirectoriesForUpdateJob()
    {
 
        $this->options->excludeGlobRules = [];
        if (!empty($_POST["excludeGlobRules"])) {
            $this->options->excludeGlobRules = $this->sanitize->sanitizeExcludeRules($_POST["excludeGlobRules"]);
        }

        $this->options->excludeSizeRules = [];
        if (!empty($_POST["excludeSizeRules"])) {
            $this->options->excludeSizeRules = $this->sanitize->sanitizeExcludeRules($_POST["excludeSizeRules"]);
        }

 
        $excludedDirectoriesRequest         = isset($_POST["excludedDirectories"]) ? $this->sanitize->sanitizeString($_POST["excludedDirectories"]) : '';
        $excludedDirectoriesRequest         = $this->dirUtils->getExcludedDirectories($excludedDirectoriesRequest);
        $this->options->excludedDirectories = array_merge($this->options->excludedDirectories, $excludedDirectoriesRequest);
 
        if (isset($_POST["extraDirectories"])) {
            $this->options->extraDirectories = explode(ScanConst::DIRECTORIES_SEPARATOR, $this->sanitize->sanitizeString($_POST["extraDirectories"]));
        }

 
        $this->options->deleteUploadsFolder = !$this->options->uploadsSymlinked && isset($_POST['cleanUploadsDir']) && $this->sanitize->sanitizeBool($_POST['cleanUploadsDir']);
 
        $this->options->backupUploadsFolder = false;
 
        $this->options->deletePluginsAndThemes = isset($_POST['cleanPluginsThemes']) && $this->sanitize->sanitizeBool($_POST['cleanPluginsThemes']);
 
        $this->options->statusBackupUploadsDir = 'skipped';
        $this->options->statusContentCleaner   = 'pending';
    }

    private function setTablesForUpdateJob()
    {
 
        $includedTables              = isset($_POST['includedTables']) ? $this->sanitize->sanitizeString($_POST['includedTables']) : '';
        $excludedTables              = isset($_POST['excludedTables']) ? $this->sanitize->sanitizeString($_POST['excludedTables']) : '';
        $selectedTablesWithoutPrefix = isset($_POST['selectedTablesWithoutPrefix']) ? $this->sanitize->sanitizeString($_POST['selectedTablesWithoutPrefix']) : '';
        $selectedTables              = new SelectedTables($includedTables, $excludedTables, $selectedTablesWithoutPrefix);
        $selectedTables->setAllTablesExcluded(empty($_POST['allTablesExcluded']) ? false : $this->sanitize->sanitizeBool($_POST['allTablesExcluded']));
        $this->options->tables = $selectedTables->getSelectedTables($this->options->networkClone);
    }





    public function start()
    {
    }
}
