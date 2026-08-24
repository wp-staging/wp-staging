<?php

namespace WPStaging\Backend\Modules\Jobs;

use Countable;
use Exception;
use WPStaging\Backend\Modules\Jobs\Exceptions\JobNotFoundException;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingCreate;
use WPStaging\Framework\Traits\TablePrefixValidator;
use WPStaging\Framework\Database\SelectedTables;
use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Utils\WpDefaultDirectories;
use WPStaging\Staging\Sites;

use function WPStaging\functions\debug_log;





class Cloning extends Job
{
    use TablePrefixValidator;




    const WPSTG_REQUEST = 'wpstg_cloning';

 
    const FILTER_CLONE_EXCLUDED_FILES_FULL_PATH = 'wpstg.clone.excluded_files_full_path';

 
    const FILTER_CLONE_EXCLUDED_FILES = 'wpstg_clone_excluded_files';




    private $db;




    private $dirUtils;




    private $sitesHelper;




    private $errorMessage;




    protected $sanitize;




    private $urls;

 
    private $pathIdentifier;

 
    protected $strUtil;




    public function initialize()
    {
        $this->db             = WPStaging::getInstance()->get("wpdb");
        $this->dirUtils       = new WpDefaultDirectories();
        $this->sitesHelper    = new Sites();
        $this->sanitize       = WPStaging::make(Sanitize::class);
        $this->urls           = WPStaging::make(Urls::class);
        $this->strUtil        = WPStaging::make(Strings::class);
        $this->pathIdentifier = WPStaging::make(PathIdentifier::class);
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }






    public function save(): bool
    {
        if (!isset($_POST) || !isset($_POST["cloneID"])) {
            $this->errorMessage = __("clone ID missing", 'wp-staging');
            return false;
        }

 
        $this->filesIndexCache->delete();

 
        $this->options->root         = str_replace(["\\", '/'], DIRECTORY_SEPARATOR, ABSPATH);
        $this->options->current      = null;
        $this->options->currentClone = null;

 
        $this->options->clone = preg_replace("#\W+#", '-', strtolower($this->sanitize->sanitizeString($_POST["cloneID"])));

 
        if (isset($_POST["cloneName"])) {
            $this->options->cloneName = sanitize_text_field($_POST["cloneName"]);
        }

 
        if (empty($this->options->cloneName) || $this->options->cloneName === $this->options->clone) {
            $this->options->cloneName = $this->maybeGenerateFriendlyName();
        }

 
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
        $this->options->excludedFiles       = Hooks::applyFilters(self::FILTER_CLONE_EXCLUDED_FILES, [
            '.DS_Store',
            '*.git',
            '*.svn',
            '*.tmp',
            'desktop.ini',
            '.gitignore',
            '*.log',
            'web.config', 
            '.wp-staging', 
            '.wp-staging-cloneable', 
        ]);

        $excludedFilesFullPath = [
            '.htaccess',
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'db.php',
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'object-cache.php',
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'advanced-cache.php',
        ];

        $hostingExclusions                      = $this->getHostingProviderExclusions();
        $this->options->tmpExcludedHostingFiles = $hostingExclusions['absolutePaths'];
        $excludedFilesFullPath                  = array_merge($excludedFilesFullPath, $hostingExclusions['files']);

        $this->options->excludedFilesFullPath = Hooks::applyFilters(self::FILTER_CLONE_EXCLUDED_FILES_FULL_PATH, $excludedFilesFullPath);

        $this->options->currentStep = 0;

 
        $this->options->job = new \stdClass();
        $this->loadLegacyExistingClones();

 
        if (isset($this->options->existingClones[$this->options->clone])) {
            $existingClone              = (array)$this->options->existingClones[$this->options->clone];
            $this->options->cloneNumber = isset($existingClone['number']) ? (int)$existingClone['number'] : 1;
            $this->options->prefix      = !empty($existingClone['prefix']) && is_string($existingClone['prefix']) ? $existingClone['prefix'] : $this->setStagingPrefix();

 
 
        } elseif (!empty($this->options->existingClones)) {
            $this->options->cloneNumber = count($this->options->existingClones) + 1;
        }

        $this->options->networkClone = false;
        if ($this->isMultisiteAndPro() && is_main_site()) {
            $this->options->networkClone = isset($_POST['networkClone']) && $this->sanitize->sanitizeBool($_POST['networkClone']);
        }

 
        $includedTables              = isset($_POST['includedTables']) ? $this->sanitize->sanitizeString($_POST['includedTables']) : '';
        $excludedTables              = isset($_POST['excludedTables']) ? $this->sanitize->sanitizeString($_POST['excludedTables']) : '';
        $selectedTablesWithoutPrefix = isset($_POST['selectedTablesWithoutPrefix']) ? $this->sanitize->sanitizeString($_POST['selectedTablesWithoutPrefix']) : '';
        $selectedTables              = new SelectedTables($includedTables, $excludedTables, $selectedTablesWithoutPrefix);
        $selectedTables->setAllTablesExcluded(empty($_POST['allTablesExcluded']) ? false : $this->sanitize->sanitizeBool($_POST['allTablesExcluded']));
        $this->options->tables = $selectedTables->getSelectedTables($this->options->networkClone);

 
        $this->options->excludeGlobRules = [];
        if (!empty($_POST["excludeGlobRules"])) {
            $this->options->excludeGlobRules = $this->sanitize->sanitizeExcludeRules($_POST["excludeGlobRules"]);
        }

 
        $this->options->excludeSizeRules = [];
        if (!empty($_POST["excludeSizeRules"])) {
            $this->options->excludeSizeRules = $this->sanitize->sanitizeExcludeRules($_POST["excludeSizeRules"]);
        }

        $this->options->uploadsSymlinked = isset($_POST['uploadsSymlinked']) && $this->sanitize->sanitizeBool($_POST['uploadsSymlinked']);

        $pluginWpContentDir = rtrim($this->directoryAdapter->getPluginWpContentDirectory(), '/\\');





        $excludedDirectories = [
            PathIdentifier::IDENTIFIER_WP_CONTENT . 'cache',
            $this->pathIdentifier->transformPathToIdentifiable($pluginWpContentDir), 
            PathIdentifier::IDENTIFIER_WP_CONTENT . WPSTG_PLUGIN_DOMAIN, 
        ];

        $excludedDirectories = array_merge($excludedDirectories, $hostingExclusions['directories']);

 
        if ($this->options->uploadsSymlinked) {
            $excludedDirectories[] = PathIdentifier::IDENTIFIER_UPLOADS;
        }

        $excludedDirectoriesRequest = isset($_POST["excludedDirectories"]) ? $this->sanitize->sanitizeString($_POST["excludedDirectories"]) : '';
        $excludedDirectoriesRequest = $this->dirUtils->getExcludedDirectories($excludedDirectoriesRequest);

        $this->options->excludedDirectories = array_merge($excludedDirectories, $excludedDirectoriesRequest);

 
        if (isset($_POST["extraDirectories"])) {
            $this->options->extraDirectories = explode(ScanConst::DIRECTORIES_SEPARATOR, $this->sanitize->sanitizeString($_POST["extraDirectories"]));
        }

 
        $this->options->useNewAdminAccount = false;
        $this->options->adminEmail         = '';
        $this->options->adminPassword      = '';

 
        $this->options->databaseServer   = 'localhost';
        $this->options->databaseUser     = '';
        $this->options->databasePassword = '';
        $this->options->databaseDatabase = '';
 
        $this->options->databasePrefix = $this->isExternalDatabase() ? $this->db->prefix : '';
        $this->options->databaseSsl    = false;

 
        $this->options->cloneDir      = '';
        $this->options->cloneHostname = '';

 
        $this->options->isEmailsAllowed         = true;
        $this->options->isCronEnabled           = true;
        $this->options->isWooSchedulerEnabled   = true;
        $this->options->isEmailsReminderEnabled = false;
        $this->options->isAutoUpdatePlugins     = false;
        $this->setAdvancedCloningOptions();

        $this->options->destinationDir      = $this->getDestinationDir();
        $this->options->destinationHostname = $this->getDestinationHostname();

        $this->options->homeHostname = $this->urls->getHomeUrlWithoutScheme();

 
        $this->options->isRunning = true;
        $this->initializeLegacyStagingRun(Job::STAGING);

 
        $this->options->ownerId = get_current_user_id();
 
        $this->saveClone();

        if (!$this->saveOptions()) {
            return false;
        }

        WPStaging::make(AnalyticsStagingCreate::class)->enqueueStartEvent($this->options->jobIdentifier, $this->options);
        $this->errorMessage = "";
        return true;
    }





    private function saveClone()
    {
 
        $this->debugLog("Cloning: {$this->options->clone}'s clone job's data is not in database, generating data");

        $this->options->existingClones[$this->options->clone] = [
            "cloneName"               => $this->options->cloneName,
            "directoryName"           => $this->options->cloneDirectoryName,
            "path"                    => trailingslashit($this->options->destinationDir),
            "url"                     => $this->getDestinationUrl(),
            "number"                  => $this->options->cloneNumber,
            "version"                 => WPStaging::getVersion(),
            "status"                  => "unfinished or broken (?)",
            "prefix"                  => $this->options->prefix,
            "datetime"                => time(),
            "useCustomDatabase"       => $this->isExternalDatabase(),
            "databaseUser"            => $this->options->databaseUser,
            "databasePassword"        => $this->options->databasePassword,
            "databaseDatabase"        => $this->options->databaseDatabase,
            "databaseServer"          => $this->options->databaseServer,
            "databasePrefix"          => $this->options->databasePrefix,
            "databaseSsl"             => (bool)$this->options->databaseSsl,
            "isCronEnabled"           => (bool)$this->options->isCronEnabled,
            "isEmailsAllowed"         => (bool)$this->options->isEmailsAllowed,
            "uploadsSymlinked"        => (bool)$this->options->uploadsSymlinked,
            "ownerId"                 => $this->options->ownerId,
            "includedTables"          => $this->options->tables,
            "excludeSizeRules"        => $this->options->excludeSizeRules,
            "excludeGlobRules"        => $this->options->excludeGlobRules,
            "excludedDirectories"     => $this->options->excludedDirectories,
            "extraDirectories"        => $this->options->extraDirectories,
            "networkClone"            => $this->isNetworkClone(),
            'useNewAdminAccount'      => $this->options->useNewAdminAccount,
            'adminEmail'              => $this->options->adminEmail,
            'adminPassword'           => $this->options->adminPassword,
            'isWooSchedulerEnabled'   => (bool)$this->options->isWooSchedulerEnabled,
            "isEmailsReminderEnabled" => (bool)$this->options->isEmailsReminderEnabled,
            'isAutoUpdatePlugins'     => (bool)$this->options->isAutoUpdatePlugins,
        ];

        if ($this->sitesHelper->updateStagingSites($this->options->existingClones) === false) {
            $this->log("Cloning: Failed to save {$this->options->clone}'s clone job data to database'");
        }
    }





    private function getDestinationUrl(): string
    {
        if (!empty($this->options->cloneHostname)) {
            return $this->options->cloneHostname;
        }

        return trailingslashit(get_site_url()) . $this->options->cloneDirectoryName;
    }





    private function getDestinationHostname(): string
    {
        if (empty($this->options->cloneHostname)) {
            return $this->urls->getHomeUrlWithoutScheme();
        }

        return $this->getHostnameWithoutScheme($this->options->cloneHostname);
    }






    private function getHostnameWithoutScheme(string $string): string
    {
        return preg_replace('#^https?://#', '', rtrim($string, '/'));
    }





    private function getDestinationDir(): string
    {
 
        if (!empty($this->options->cloneDir) & (trailingslashit($this->options->cloneDir) === trailingslashit(WPStaging::getWPpath()))) {
            $this->returnException('Error: Target path must be different from the root of the production website.');
        }

 
        if (!empty($this->options->cloneDir)) {
            return trailingslashit($this->options->cloneDir);
        }

 
        $cloneDestinationPath = $this->directoryAdapter->getAbsPath() . $this->options->cloneDirectoryName;

        if (!is_writable($this->directoryAdapter->getAbsPath())) {
            $stagingSiteDirectory = $this->directoryAdapter->getStagingSiteDirectoryInsideWpcontent();
            if ($stagingSiteDirectory === false) {
                debug_log(esc_html('Fail to get destination directory. The staging sites destination folder cannot be created.'));
                $this->returnException('The staging sites directory is not writable. Please choose another path.');
            }

            $cloneDestinationPath = trailingslashit($stagingSiteDirectory) . $this->options->cloneDirectoryName;
            if (empty($this->options->cloneHostname)) {
                $this->options->cloneHostname = trailingslashit($this->directoryAdapter->getStagingSiteUrl()) . $this->options->cloneDirectoryName;
            }
        }

        $this->options->cloneDir = trailingslashit($cloneDestinationPath);
        return $this->options->cloneDir;
    }




    private function setStagingPrefix()
    {
 
 
        for ($i = 0; $i <= 10000; $i++) {
            $this->options->prefix = !empty($this->options->existingClones) && $this->options->existingClones instanceof Countable
                ? 'wpstg' . (count($this->options->existingClones) + $i) . '_'
                : 'wpstg' . $i . '_';

            $sql    = "SHOW TABLE STATUS LIKE '{$this->options->prefix}%'";
            $tables = $this->db->get_results($sql);

 
            if (!$tables) {
                return $this->options->prefix;
            }
        }

        $message = sprintf("Fatal Error: Can not create staging prefix. '%s' already exists! Stopping for security reasons. Contact support@wp-staging.com", $this->options->prefix);
        $this->returnException($message);
        wp_die(esc_html($message));
    }






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

        if ($this->isWpStagingReservedPrefix($this->options->databasePrefix)) {
            $this->returnException($this->getReservedPrefixErrorMessage($this->options->databasePrefix));
        }

 
        return $this->{$methodName}();
    }







    private function handleJobResponse($response, string $nextJob)
    {
 
        if ($response->status !== true) {
            return $response;
        }

        $this->options->job         = new \stdClass();
        $this->options->currentJob  = $nextJob;
        $this->options->currentStep = 0;
        $this->options->totalSteps  = 0;

 
        $this->saveOptions();

        return $response;
    }






    public function jobPreserveDataFirstStep()
    {
        $this->writeJobSpecificLogStartHeader();

        $preserve = new PreserveDataFirstStep();
        return $this->handleJobResponse($preserve->start(), 'database');
    }






    public function jobDatabase()
    {
        $database = new Database();
        return $this->handleJobResponse($database->start(), "SearchReplace");
    }






    public function jobSearchReplace()
    {
        $searchReplace = new SearchReplace();
        return $this->handleJobResponse($searchReplace->start(), "PreserveDataSecondStep");
    }






    public function jobPreserveDataSecondStep()
    {
        $preserve = new PreserveDataSecondStep();
        return $this->handleJobResponse($preserve->start(), 'directories');
    }






    public function jobDirectories()
    {
        $directories = new Directories();
        return $this->handleJobResponse($directories->start(), "files");
    }






    public function jobFiles()
    {
        $files = new Files();
        return $this->handleJobResponse($files->start(), "data");
    }






    public function jobData()
    {
        $dataJob = $this->getDataJob();
        return $this->handleJobResponse($dataJob->start(), "finish");
    }






    public function jobFinish()
    {
 
 
 
        $accessToken = new AccessToken();
        $accessToken->generateNewToken();

        $finish = new Finish();
        return $this->handleJobResponse($finish->start(), '');
    }




    public function getDataJob(): Data
    {
        return new Data();
    }




    protected function setAdvancedCloningOptions()
    {
 
    }




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





    private function maybeGenerateFriendlyName(): string
    {
 
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

 
        shuffle($nameList);

 
        $stagingSites = $this->sitesHelper->tryGettingStagingSites();
        foreach ($nameList as $name) {
 
            $name    = sanitize_text_field($name);
            $dirPath = ABSPATH . $name;
 
            if (file_exists($dirPath)) {
                continue;
            }

 
            if (!$this->isStagingSiteNameExists($name, $stagingSites)) {
                return $name;
            }
        }

 
        return (string)$this->options->clone;
    }







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
