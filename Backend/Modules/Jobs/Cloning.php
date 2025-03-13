<?php

namespace WPStaging\Backend\Modules\Jobs;

use Countable;
use Exception;
use WPStaging\Backend\Modules\Jobs\Exceptions\JobNotFoundException;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingCreate;
use WPStaging\Framework\Database\SelectedTables;
use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Utils\WpDefaultDirectories;
use WPStaging\Staging\Sites;

use function WPStaging\functions\debug_log;

/**
 * Class Cloning
 * @package WPStaging\Backend\Modules\Jobs
 */
class Cloning extends Job
{
    /**
     * @var string
     */
    const WPSTG_REQUEST = 'wpstg_cloning';

    /**
     * @var object
     */
    private $db;

    /**
     * @var WpDefaultDirectories
     */
    private $dirUtils;

    /**
     * @var Sites
     */
    private $sitesHelper;

    /**
     * @var string
     */
    private $errorMessage;

    /**
     * @var Sanitize
     */
    protected $sanitize;

    /**
     * @var Urls
     */
    private $urls;

    /** @var Directory */
    private $dirAdapter;

    /** @var PathIdentifier */
    private $pathIdentifier;

    /** @var Strings */
    protected $strUtil;

    /**
     * Initialize is called in \Job
     */
    public function initialize()
    {
        $this->db             = WPStaging::getInstance()->get("wpdb");
        $this->dirUtils       = new WpDefaultDirectories();
        $this->sitesHelper    = new Sites();
        $this->sanitize       = WPStaging::make(Sanitize::class);
        $this->urls           = WPStaging::make(Urls::class);
        $this->dirAdapter     = WPStaging::make(Directory::class);
        $this->strUtil        = WPStaging::make(Strings::class);
        $this->pathIdentifier = WPStaging::make(PathIdentifier::class);
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * Save Chosen Cloning Settings
     * @return bool
     * @throws \Exception
     */
    public function save(): bool
    {
        if (!isset($_POST) || !isset($_POST["cloneID"])) {
            $this->errorMessage = __("clone ID missing", 'wp-staging');
            return false;
        }

        // Delete files index cache file
        $this->filesIndexCache->delete();

        // Generate Options
        // Clone ID -> timestamp (time at which this clone creation initiated)
        $this->options->clone = preg_replace("#\W+#", '-', strtolower($this->sanitize->sanitizeString($_POST["cloneID"])));

        // Clone Name -> Site name that user input
        if (isset($_POST["cloneName"])) {
            $this->options->cloneName = sanitize_text_field($_POST["cloneName"]);
        }

        // If it's empty or it's a clone Id, try setting it to a random human-friendly name
        if (empty($this->options->cloneName) || $this->options->cloneName === $this->options->clone) {
            $this->options->cloneName = $this->maybeGenerateFriendlyName();
        }

        // The slugified version of Clone Name (to use in directory creation)
        $this->options->cloneDirectoryName = $this->sitesHelper->sanitizeDirectoryName($this->options->cloneName);
        $result                            = $this->sitesHelper->isCloneExists($this->options->cloneDirectoryName);
        if ($result !== false) {
            $this->errorMessage = $result;
            return false;
        }

        $this->options->cloneNumber         = 1;
        $this->options->prefix              = $this->setStagingPrefix();
        $this->options->includedDirectories = [];
        $this->options->excludedDirectories = [];
        $this->options->extraDirectories    = [];
        $this->options->excludedFiles       = apply_filters('wpstg_clone_excluded_files', [
            '.DS_Store',
            '*.git',
            '*.svn',
            '*.tmp',
            'desktop.ini',
            '.gitignore',
            '*.log',
            'web.config', // Important: Windows IIS configuration file. Must not be in the staging site!
            '.wp-staging', // Determines if a site is a staging site
            '.wp-staging-cloneable', // File that makes the staging site cloneable.
        ]);

        $excludedFilesFullPath = [
            '.htaccess',
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'db.php',
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'object-cache.php',
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'advanced-cache.php',
        ];

        $this->options->tmpExcludedGoDaddyFiles = [];
        $muPluginsDir                           = trailingslashit($this->dirAdapter->getMuPluginsDirectory());
        if (file_exists($muPluginsDir . 'gd-system-plugin.php')) {
            $excludedFilesFullPath[]                  = PathIdentifier::IDENTIFIER_MUPLUGINS . 'gd-system-plugin.php';
            $this->options->tmpExcludedGoDaddyFiles[] = $muPluginsDir . 'gd-system-plugin.php';
        }

        $this->options->excludedFilesFullPath = apply_filters('wpstg.clone.excluded_files_full_path', $excludedFilesFullPath);

        $this->options->currentStep = 0;

        // Job
        $this->options->job = new \stdClass();

        // Check if clone data already exists and use that one
        if (isset($this->options->existingClones[$this->options->clone])) {
            $this->options->cloneNumber = $this->options->existingClones[$this->options->clone]->number;
            $this->options->prefix      = isset($this->options->existingClones[$this->options->clone]->prefix) ? $this->options->existingClones[$this->options->clone]->prefix : $this->setStagingPrefix();

        // Clone does not exist but there are other clones in db
        // Get data and increment it
        } elseif (!empty($this->options->existingClones)) {
            $this->options->cloneNumber = count($this->options->existingClones) + 1;
        }

        $this->options->networkClone = false;
        if ($this->isMultisiteAndPro() && is_main_site()) {
            $this->options->networkClone = isset($_POST['networkClone']) && $this->sanitize->sanitizeBool($_POST['networkClone']);
        }

        // Included Tables / Prefixed Table - Excluded Tables
        $includedTables              = isset($_POST['includedTables']) ? $this->sanitize->sanitizeString($_POST['includedTables']) : '';
        $excludedTables              = isset($_POST['excludedTables']) ? $this->sanitize->sanitizeString($_POST['excludedTables']) : '';
        $selectedTablesWithoutPrefix = isset($_POST['selectedTablesWithoutPrefix']) ? $this->sanitize->sanitizeString($_POST['selectedTablesWithoutPrefix']) : '';
        $selectedTables              = new SelectedTables($includedTables, $excludedTables, $selectedTablesWithoutPrefix);
        $selectedTables->setAllTablesExcluded(empty($_POST['allTablesExcluded']) ? false : $this->sanitize->sanitizeBool($_POST['allTablesExcluded']));
        $this->options->tables = $selectedTables->getSelectedTables($this->options->networkClone);

        // Exclude File Size Rules
        $this->options->excludeGlobRules = [];
        if (!empty($_POST["excludeGlobRules"])) {
            $this->options->excludeGlobRules = $this->sanitize->sanitizeExcludeRules($_POST["excludeGlobRules"]);
        }

        // Exclude Glob Rules
        $this->options->excludeSizeRules = [];
        if (!empty($_POST["excludeSizeRules"])) {
            $this->options->excludeSizeRules = $this->sanitize->sanitizeExcludeRules($_POST["excludeSizeRules"]);
        }

        $this->options->uploadsSymlinked = isset($_POST['uploadsSymlinked']) && $this->sanitize->sanitizeBool($_POST['uploadsSymlinked']);

        $pluginWpContentDir = rtrim($this->dirAdapter->getPluginWpContentDirectory(), '/\\');

        /**
         * @see /WPStaging/Framework/CloningProcess/ExcludedPlugins.php to exclude plugins
         * Only add other directories here
         */
        $excludedDirectories = [
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'cache',
            $this->pathIdentifier->transformPathToIdentifiable($pluginWpContentDir), // wp-content/wp-staging
            PathIdentifier::IDENTIFIER_WP_CONTENT . WPSTG_PLUGIN_DOMAIN, // Extra caution if pluginWpContentDir changed later
        ];

        // Go Daddy related exclusions
        if (is_dir(trailingslashit($this->dirAdapter->getMuPluginsDirectory()) . 'gd-system-plugin')) {
            $excludedDirectories[] = PathIdentifier::IDENTIFIER_MUPLUGINS . 'gd-system-plugin';
            $excludedDirectories[] = PathIdentifier::IDENTIFIER_MUPLUGINS . 'vendor';

            $this->options->tmpExcludedGoDaddyFiles[] = $muPluginsDir . 'gd-system-plugin';
            $this->options->tmpExcludedGoDaddyFiles[] = $muPluginsDir . 'vendor';
        }

        // Add upload folder to list of excluded directories for push if symlink option is enabled
        if ($this->options->uploadsSymlinked) {
            $excludedDirectories[] = PathIdentifier::IDENTIFIER_UPLOADS;
        }

        $excludedDirectoriesRequest = isset($_POST["excludedDirectories"]) ? $this->sanitize->sanitizeString($_POST["excludedDirectories"]) : '';
        $excludedDirectoriesRequest = $this->dirUtils->getExcludedDirectories($excludedDirectoriesRequest);

        $this->options->excludedDirectories = array_merge($excludedDirectories, $excludedDirectoriesRequest);

        // Extra Directories
        if (isset($_POST["extraDirectories"])) {
            $this->options->extraDirectories = explode(ScanConst::DIRECTORIES_SEPARATOR, $this->sanitize->sanitizeString($_POST["extraDirectories"]));
        }

        // New Admin Account
        $this->options->useNewAdminAccount = false;
        $this->options->adminEmail         = '';
        $this->options->adminPassword      = '';

        // External Database
        $this->options->databaseServer   = 'localhost';
        $this->options->databaseUser     = '';
        $this->options->databasePassword = '';
        $this->options->databaseDatabase = '';
        // isExternalDatabase() depends upon databaseUser and databasePassword,
        // Make sure they are set before calling this.
        $this->options->databasePrefix = $this->isExternalDatabase() ? $this->db->prefix : '';
        $this->options->databaseSsl    = false;

        // Custom Hosts
        $this->options->cloneDir      = '';
        $this->options->cloneHostname = '';

        // Default options for FREE version
        $this->options->emailsAllowed         = true;
        $this->options->cronDisabled          = false;
        $this->options->wooSchedulerDisabled  = false;
        $this->options->emailsReminderAllowed = false;
        $this->options->isAutoUpdatePlugins   = false;
        $this->setAdvancedCloningOptions();

        $this->options->destinationDir      = $this->getDestinationDir();
        $this->options->destinationHostname = $this->getDestinationHostname();

        $this->options->homeHostname = $this->urls->getHomeUrlWithoutScheme();

        // Process lock state
        $this->options->isRunning = true;

        // id of the user creating the clone
        $this->options->ownerId = get_current_user_id();
        // Save Clone data
        $this->saveClone();

        WPStaging::make(AnalyticsStagingCreate::class)->enqueueStartEvent($this->options->jobIdentifier, $this->options);

        $this->errorMessage = "";
        return $this->saveOptions();
    }

    /**
     * Save clone data initially
     * @return void
     */
    private function saveClone()
    {
        // Save new clone data
        $this->debugLog("Cloning: {$this->options->clone}'s clone job's data is not in database, generating data");

        $this->options->existingClones[$this->options->clone] = [
            "cloneName"             => $this->options->cloneName,
            "directoryName"         => $this->options->cloneDirectoryName,
            "path"                  => trailingslashit($this->options->destinationDir),
            "url"                   => $this->getDestinationUrl(),
            "number"                => $this->options->cloneNumber,
            "version"               => WPStaging::getVersion(),
            "status"                => "unfinished or broken (?)",
            "prefix"                => $this->options->prefix,
            "datetime"              => time(),
            "databaseUser"          => $this->options->databaseUser,
            "databasePassword"      => $this->options->databasePassword,
            "databaseDatabase"      => $this->options->databaseDatabase,
            "databaseServer"        => $this->options->databaseServer,
            "databasePrefix"        => $this->options->databasePrefix,
            "databaseSsl"           => (bool)$this->options->databaseSsl,
            "cronDisabled"          => (bool)$this->options->cronDisabled,
            "emailsAllowed"         => (bool)$this->options->emailsAllowed,
            "uploadsSymlinked"      => (bool)$this->options->uploadsSymlinked,
            "ownerId"               => $this->options->ownerId,
            "includedTables"        => $this->options->tables,
            "excludeSizeRules"      => $this->options->excludeSizeRules,
            "excludeGlobRules"      => $this->options->excludeGlobRules,
            "excludedDirectories"   => $this->options->excludedDirectories,
            "extraDirectories"      => $this->options->extraDirectories,
            "networkClone"          => $this->isNetworkClone(),
            'useNewAdminAccount'    => $this->options->useNewAdminAccount,
            'adminEmail'            => $this->options->adminEmail,
            'adminPassword'         => $this->options->adminPassword,
            'wooSchedulerDisabled'  => (bool)$this->options->wooSchedulerDisabled,
            "emailsReminderAllowed" => (bool)$this->options->emailsReminderAllowed,
            'isAutoUpdatePlugins'   => (bool)$this->options->isAutoUpdatePlugins,
        ];

        if ($this->sitesHelper->updateStagingSites($this->options->existingClones) === false) {
            $this->log("Cloning: Failed to save {$this->options->clone}'s clone job data to database'");
        }
    }

    /**
     * Get destination Hostname depending on whether WP has been installed in sub dir or not
     * @return string
     */
    private function getDestinationUrl(): string
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
    private function getDestinationHostname(): string
    {
        if (empty($this->options->cloneHostname)) {
            return $this->urls->getHomeUrlWithoutScheme();
        }

        return $this->getHostnameWithoutScheme($this->options->cloneHostname);
    }

    /**
     * Return Hostname without scheme
     * @param string $string
     * @return string
     */
    private function getHostnameWithoutScheme(string $string): string
    {
        return preg_replace('#^https?://#', '', rtrim($string, '/'));
    }

    /**
     * Get Destination Directory including staging subdirectory
     * @return string
     */
    private function getDestinationDir(): string
    {
        // Throw fatal error
        if (!empty($this->options->cloneDir) & (trailingslashit($this->options->cloneDir) === trailingslashit(WPStaging::getWPpath()))) {
            $this->returnException('Error: Target path must be different from the root of the production website.');
        }

        // custom destination has been set
        if (!empty($this->options->cloneDir)) {
            return trailingslashit($this->options->cloneDir);
        }

        // No custom destination so default path will be in a subfolder of root or inside wp-content
        $cloneDestinationPath = $this->dirAdapter->getAbsPath() . $this->options->cloneDirectoryName;

        if ($this->isPro() && !is_writable($this->dirAdapter->getAbsPath())) {
            $stagingSiteDirectory = $this->dirAdapter->getStagingSiteDirectoryInsideWpcontent();
            if ($stagingSiteDirectory === false) {
                debug_log(esc_html('Fail to get destination directory. The staging sites destination folder cannot be created.'));
                $this->returnException('The staging sites directory is not writable. Please choose another path.');
            }

            $cloneDestinationPath = trailingslashit($stagingSiteDirectory) . $this->options->cloneDirectoryName;
            if (empty($this->options->cloneHostname)) {
                $this->options->cloneHostname = trailingslashit($this->dirAdapter->getStagingSiteUrl()) . $this->options->cloneDirectoryName;
            }
        }

        $this->options->cloneDir = trailingslashit($cloneDestinationPath);
        return $this->options->cloneDir;
    }

    /**
     * Create a new staging prefix that does not exist in database
     */
    private function setStagingPrefix()
    {
        // Find a new prefix that does not already exist in database.
        // Loop through up to 1000 different possible prefixes should be enough here;)
        for ($i = 0; $i <= 10000; $i++) {
            $this->options->prefix = !empty($this->options->existingClones) && $this->options->existingClones instanceof Countable
                ? 'wpstg' . (count($this->options->existingClones) + $i) . '_'
                : 'wpstg' . $i . '_';

            $sql    = "SHOW TABLE STATUS LIKE '{$this->options->prefix}%'";
            $tables = $this->db->get_results($sql);

            // Prefix does not exist. We can use it
            if (!$tables) {
                return $this->options->prefix;
            }
        }

        $message = sprintf("Fatal Error: Can not create staging prefix. '%s' already exists! Stopping for security reasons. Contact support@wp-staging.com", $this->options->prefix);
        $this->returnException($message);
        wp_die(esc_html($message));
    }


    /**
     * Start the cloning job
     * @throws JobNotFoundException
     */
    public function start()
    {
        if (!is_object($this->options)) {
            return;
        }

        if (!property_exists($this->options, 'currentJob') || $this->options->currentJob === null) {
            $this->log("Cloning job finished");
            return true;
        }

        $methodName = "job" . ucwords($this->options->currentJob);

        if (!method_exists($this, $methodName)) {
            $this->log("Can't execute job; Job's method $methodName is not found");
            throw new JobNotFoundException($methodName);
        }

        if ($this->options->databasePrefix === $this->db->prefix && $this->isStagingDatabaseSameAsProductionDatabase()) {
            $this->returnException('Table prefix for staging site can not be identical to live database if staging site will be cloned into production database! Please start over and change the table prefix or destination database.');
        }

        if (defined('WPSTG_IS_DEV') && WPSTG_IS_DEV === true) {
            return $this->{$methodName}();
        }

        $tmpPrefixes = [
            DatabaseImporter::TMP_DATABASE_PREFIX,
            DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP,
        ];

        if (in_array($this->options->databasePrefix, $tmpPrefixes)) {
            $this->returnException('Prefix wpstgtmp_ and wpstgbak_ are preserved by WP Staging and cannot be used for CLONING purpose! Please start over and change the table prefix.');
        }

        // Call the job
        return $this->{$methodName}();
    }

    /**
     * @param object $response
     * @param string $nextJob
     * @return object
     * @throws \Exception
     */
    private function handleJobResponse($response, string $nextJob)
    {
        // Job is not done
        if ($response->status !== true) {
            return $response;
        }

        $this->options->job         = new \stdClass();
        $this->options->currentJob  = $nextJob;
        $this->options->currentStep = 0;
        $this->options->totalSteps  = 0;

        // Save options
        $this->saveOptions();

        return $response;
    }

    /**
     * Copy data from staging site to temporary column to use it later
     * @return object
     * @throws \Exception
     */
    public function jobPreserveDataFirstStep()
    {
        $this->writeJobSpecificLogStartHeader();

        $preserve = new PreserveDataFirstStep();
        return $this->handleJobResponse($preserve->start(), 'database');
    }

    /**
     * Clone Database
     * @return object
     * @throws \Exception
     */
    public function jobDatabase()
    {
        $database = new Database();
        return $this->handleJobResponse($database->start(), "SearchReplace");
    }

    /**
     * Search & Replace
     * @return object
     * @throws \Exception
     */
    public function jobSearchReplace()
    {
        $searchReplace = new SearchReplace();
        return $this->handleJobResponse($searchReplace->start(), "PreserveDataSecondStep");
    }

    /**
     * Copy tmp data back to staging site
     * @return object
     * @throws \Exception
     */
    public function jobPreserveDataSecondStep()
    {
        $preserve = new PreserveDataSecondStep();
        return $this->handleJobResponse($preserve->start(), 'directories');
    }

    /**
     * Get All Files From Selected Directories Recursively Into a File
     * @return object
     * @throws \Exception
     */
    public function jobDirectories()
    {
        $directories = new Directories();
        return $this->handleJobResponse($directories->start(), "files");
    }

    /**
     * Copy Files
     * @return object
     * @throws \Exception
     */
    public function jobFiles()
    {
        $files = new Files();
        return $this->handleJobResponse($files->start(), "data");
    }

    /**
     * Replace Data
     * @return object
     * @throws \Exception
     */
    public function jobData()
    {
        $dataJob = $this->getDataJob();
        return $this->handleJobResponse($dataJob->start(), "finish");
    }

    /**
     * Save Clone Data
     * @return object
     * @throws \Exception
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

    /**
     * @return Data
     */
    public function getDataJob(): Data
    {
        return new Data();
    }

    /**
     * @return void
     */
    protected function setAdvancedCloningOptions()
    {
        // no-op
    }

    /**
     * @return void
     */
    private function writeJobSpecificLogStartHeader()
    {

        $jobName = empty($this->options->mainJob) ? 'Unknown' : $this->options->mainJob;

        switch ($jobName) {
            case Job::UPDATE:
                $jobName = 'Update';
                break;
            case Job::RESET:
                $jobName = 'Reset';
                break;
            case Job::STAGING:
                $jobName = 'Cloning';
                break;
            default:
                $jobName = 'Unknown';
                break;
        }

        $this->log('#################### Start ' . $jobName . ' Job ####################', 'INFO');
        if ($jobName !== 'Cloning' && !empty($this->options->clone)) {
            $this->logger->info(esc_html('Staging Site ID: ' . $this->options->clone));
            $this->logger->info(esc_html('Staging Site: ' . $this->options->cloneName));
        }

        $this->logger->writeLogHeader();
        $this->logger->writeInstalledPluginsAndThemes();
        $this->addJobSettingsToLogs($jobName);
    }

    /**
     * @return string The generated friendly name or clone Id by default
     * @throws WPStagingException
     */
    private function maybeGenerateFriendlyName(): string
    {
        // List of predefined names to choose from
        $nameList = [
            "enterprise",
            "voyager",
            "defiant",
            "discovery",
            "excelsior",
            "intrepid",
            "constitution",
            "reliant",
            "grissom",
            "yamato",
            "excelsior",
            "venture",
            "cerritos",
            "prometheus",
            "bellerophon",
            "sanpablo",
            "sutherland",
            "shenzhou",
            "titan",
            "reliant",
            "stargazer",
            "franklin",
            "protostar",
        ];

        // Randomly shuffle the list of names
        shuffle($nameList);

        // Get the list of staging sites
        $stagingSites = $this->sitesHelper->tryGettingStagingSites();
        foreach ($nameList as $name) {
            // Sanitize the name to ensure it is safe for use
            $name    = sanitize_text_field($name);
            $dirPath = ABSPATH . $name;
            // Check if the directory exists
            if (file_exists($dirPath)) {
                continue;
            }

            // If the directory is free, then check the database
            if (!$this->isStagingSiteNameExists($name, $stagingSites)) {
                return $name;
            }
        }

        // If all predefined names are taken, return a clone Id
        return (string)$this->options->clone;
    }

    /**
     * Check if the name already exists in the staging sites $stagingSites
     * @param string $name
     * @param array $stagingSites
     * @return bool
     */
    private function isStagingSiteNameExists(string $name, array $stagingSites): bool
    {
        foreach ($stagingSites as $site) {
            if ($site['directoryName'] === $name) {
                return true;
            }
        }

        return false;
    }
}
