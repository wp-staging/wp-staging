<?php

namespace WPStaging\Backend\Modules\Jobs;

use Countable;
use Exception;
use WPStaging\Backend\Modules\Jobs\Exceptions\JobNotFoundException;
use WPStaging\Backup\Ajax\Restore\PrepareRestore;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingCreate;
use WPStaging\Framework\Database\SelectedTables;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\Staging\Sites;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Utils\WpDefaultDirectories;

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
    private $sanitize;

    /**
     * @var Urls
     */
    private $urls;

    /** @var Directory */
    private $dirAdapter;

    /**
     * Initialize is called in \Job
     */
    public function initialize()
    {
        $this->db          = WPStaging::getInstance()->get("wpdb");
        $this->dirUtils    = new WpDefaultDirectories();
        $this->sitesHelper = new Sites();
        $this->sanitize    = WPStaging::make(Sanitize::class);
        $this->urls        = WPStaging::make(Urls::class);
        $this->dirAdapter  = WPStaging::make(Directory::class);
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
        // Clone Name -> Site name that user input, if user left it empty it will be Clone ID
        $this->options->cloneName = isset($_POST["cloneName"]) ? sanitize_text_field($_POST["cloneName"]) : '';
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
            $this->options->prefix = isset($this->options->existingClones[$this->options->clone]->prefix) ? $this->options->existingClones[$this->options->clone]->prefix : $this->setStagingPrefix();

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

        /**
         * @see /WPStaging/Framework/CloningProcess/ExcludedPlugins.php to exclude plugins
         * Only add other directories here
         */
        $excludedDirectories = [
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'cache',
        ];

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

        $this->options->databaseServer = 'localhost';
        if (!empty($_POST["databaseServer"])) {
            $this->options->databaseServer = $this->sanitize->sanitizeString($_POST["databaseServer"]);
        }

        $this->options->databaseUser = '';
        if (!empty($_POST["databaseUser"])) {
            $this->options->databaseUser = $this->sanitize->sanitizeString($_POST["databaseUser"]);
        }

        $this->options->databasePassword = '';
        if (!empty($_POST["databasePassword"])) {
            $this->options->databasePassword = $this->sanitize->sanitizePassword($_POST["databasePassword"]);
        }

        $this->options->databaseDatabase = '';
        if (!empty($_POST["databaseDatabase"])) {
            $this->options->databaseDatabase = $this->sanitize->sanitizeString($_POST["databaseDatabase"]);
        }

        // isExternalDatabase() depends upon databaseUser and databasePassword,
        // Make sure they are set before calling this.
        $this->options->databasePrefix = $this->isExternalDatabase() ? $this->db->prefix : '';
        if (!empty($_POST["databasePrefix"])) {
            $this->options->databasePrefix = $this->maybeAppendUnderscorePrefix($this->sanitize->sanitizeString($_POST["databasePrefix"]));
        }

        $this->options->databaseSsl = false;
        if (isset($_POST["databaseSsl"]) && 'true' === $this->sanitize->sanitizeString($_POST["databaseSsl"])) {
            $this->options->databaseSsl = true;
        }

        $this->options->cloneDir = '';
        if (!empty($_POST["cloneDir"])) {
            $this->options->cloneDir = trailingslashit(wpstg_urldecode($this->sanitize->sanitizeString($_POST["cloneDir"])));
        }

        $this->options->cloneHostname = '';
        if (!empty($_POST["cloneHostname"])) {
            $this->options->cloneHostname = trim($this->sanitize->sanitizeString($_POST["cloneHostname"]));
        }

        // Make sure it is always enabled for free version
        $this->options->emailsAllowed = true;
        $this->options->cronDisabled = false;

        if (defined('WPSTGPRO_VERSION')) {
            $this->options->emailsAllowed = apply_filters(
                'wpstg_cloning_email_allowed',
                isset($_POST['emailsAllowed']) && $this->sanitize->sanitizeBool($_POST['emailsAllowed'])
            );
            $this->options->cronDisabled = !empty($_POST['cronDisabled']) ? $this->sanitize->sanitizeBool($_POST['cronDisabled']) : false;
        }

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
            "cloneName"           => $this->options->cloneName,
            "directoryName"       => $this->options->cloneDirectoryName,
            "path"                => trailingslashit($this->options->destinationDir),
            "url"                 => $this->getDestinationUrl(),
            "number"              => $this->options->cloneNumber,
            "version"             => WPStaging::getVersion(),
            "status"              => "unfinished or broken (?)",
            "prefix"              => $this->options->prefix,
            "datetime"            => time(),
            "databaseUser"        => $this->options->databaseUser,
            "databasePassword"    => $this->options->databasePassword,
            "databaseDatabase"    => $this->options->databaseDatabase,
            "databaseServer"      => $this->options->databaseServer,
            "databasePrefix"      => $this->options->databasePrefix,
            "databaseSsl"         => (bool)$this->options->databaseSsl,
            "cronDisabled"        => (bool)$this->options->cronDisabled,
            "emailsAllowed"       => (bool)$this->options->emailsAllowed,
            "uploadsSymlinked"    => (bool)$this->options->uploadsSymlinked,
            "ownerId"             => $this->options->ownerId,
            "includedTables"      => $this->options->tables,
            "excludeSizeRules"    => $this->options->excludeSizeRules,
            "excludeGlobRules"    => $this->options->excludeGlobRules,
            "excludedDirectories" => $this->options->excludedDirectories,
            "extraDirectories"    => $this->options->extraDirectories,
            "networkClone"        => $this->isNetworkClone(),
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
     * Make sure prefix ends with underscore
     *
     * @param string $string
     * @return string
     */
    private function maybeAppendUnderscorePrefix(string $string): string
    {
        $lastCharacter = substr($string, -1);
        if ($lastCharacter === '_') {
            return $string;
        }

        return $string . '_';
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

        if (defined('WPSTG_DEV') && WPSTG_DEV === true) {
            return $this->{$methodName}();
        }

        $tmpPrefixes = [
            PrepareRestore::TMP_DATABASE_PREFIX,
            PrepareRestore::TMP_DATABASE_PREFIX_TO_DROP,
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
        return $this->handleJobResponse((new Data())->start(), "finish");
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
    }
}
