<?php

namespace WPStaging\Backend\Modules\Jobs;

use DateInterval;
use DateTime;
use Exception;
use stdClass;
use WPStaging\Core\DTO\Settings;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Database\ExcludedTables;
use WPStaging\Framework\Interfaces\ShutdownableInterface;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Utils\Math;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Framework\Database\WpDbInfo;
use WPStaging\Framework\Security\UniqueIdentifier;
use WPStaging\Framework\Utils\Cache\Cache;

/**
 * Class Job
 * @package WPStaging\Backend\Modules\Jobs
 */
abstract class Job implements ShutdownableInterface
{
    use ResourceTrait;

    /**
     * @var string
     */
    const PUSH    = 'push';

    /**
     * @var string
     */
    const STAGING = 'cloning';

    /**
     * @var string
     */
    const RESET   = 'resetting';

    /**
     * @var string
     */
    const UPDATE  = 'updating';

    /**
     * Temp file base name for files index for cloning and push
     * @var string
     */
    const FILES_INDEX_KEY = 'clone_files_index';

    /**
     * Temp file base name that contain clone related data for cloning and push
     * @var string
     */
    const CLONE_OPTIONS_KEY = 'clone_options';

    /**
     * @var Cache
     */
    protected $cloneOptionCache;

    /**
     * @var Cache
     */
    protected $filesIndexCache;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var object
     */
    protected $options;

    /**
     * @var object
     */
    protected $settings;

    /**
     * Multisite home domain without scheme
     * @var string
     */
    protected $baseUrl;

    /** @var ExcludedTables */
    protected $excludedTableService;

    /** @var UniqueIdentifier */
    protected $identifier;

    /** @var Math */
    protected $utilsMath;

    /** @var SystemInfo */
    protected $systemInfo;

    /**
     * Job constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->utilsMath = new Math();

        $this->excludedTableService = new ExcludedTables();

        // Services
        //$this->logger     = WPStaging::make(Logger::class);
        $this->logger     = WPStaging::getInstance()->get("logger");
        $this->systemInfo = WPStaging::make(SystemInfo::class);
        $this->identifier = WPStaging::make(UniqueIdentifier::class);

        $this->setupCacheFiles();

        // Settings and Options
        $this->options  = $this->cloneOptionCache->get();
        // Convert into object
        $this->options  = json_decode(json_encode($this->options));
        $this->settings = (object)((new Settings())->setDefault());

        if (!$this->options) {
            $this->options = new stdClass();
        }

        if (isset($this->options->existingClones) && is_object($this->options->existingClones)) {
            $this->options->existingClones = json_decode(json_encode($this->options->existingClones), true);
        }

        $this->initialize();
    }

    /**
     * To be override by child classes
     * @return void
     */
    public function initialize()
    {
        // do nothing
    }

    /**
     * @todo can be removed?
     * @return void
     */
    public function onWpShutdown()
    {
        // do nothing
    }

    protected function setupCacheFiles()
    {
        // For clone options
        $this->cloneOptionCache = WPStaging::make(Cache::class);
        $this->cloneOptionCache->setLifetime(-1); // Non-expireable file
        $this->cloneOptionCache->setPath(WPStaging::getContentDir());
        $this->cloneOptionCache->setFileName(self::CLONE_OPTIONS_KEY);

        // For files index to copy files
        $this->filesIndexCache = WPStaging::make(Cache::class);
        $this->filesIndexCache->setLifetime(-1); // Non-expireable file
        $this->filesIndexCache->setPath(WPStaging::getContentDir());
        $this->filesIndexCache->setFileName(self::FILES_INDEX_KEY);

        // For other purposes
        $this->cache = WPStaging::make(Cache::class);
        $this->cache->setLifetime(-1); // Non-expireable file
        $this->cache->setPath(WPStaging::getContentDir());
    }

    /**
     * @param null|array|object $options
     * @return bool
     * @throws Exception
     */
    public function saveOptions($options = null)
    {
        // Get default options
        if ($options === null) {
            $options = $this->options;
        }

        if (!is_object($options)) {
            return false;
        }

        $now                = new DateTime();
        $options->expiresAt = $now->add(new DateInterval('P1D'))->format('Y-m-d H:i:s');

        if (!property_exists($options, 'jobIdentifier')) {
            $options->jobIdentifier = rand(0, 2147483647); // 32 bits int max
        }

        // Ensure that it is an object
        $options = json_decode(json_encode($options));
        $result  = $this->cloneOptionCache->save($options);

        return $result !== false;
    }

    /**
     * @return object
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get current time in seconds
     * @return float
     */
    protected function time()
    {
        $time = microtime();
        $time = explode(' ', $time);
        $time = (float)$time[1] + (float)$time[0];
        return $time;
    }

    /**
     * @return bool
     */
    public function isOverThreshold()
    {
        // Check if the memory is over threshold
        $usedMemory        = $this->getMemoryPeakUsage();
        $maxMemoryLimit    = $this->getMaxMemoryLimit();
        $scriptMemoryLimit = $this->getScriptMemoryLimit();

        $this->debugLog(
            sprintf(
                "Used Memory: %s Max Memory Limit: %s Max Script Memory Limit: %s",
                size_format($usedMemory),
                size_format($maxMemoryLimit),
                size_format($scriptMemoryLimit)
            ),
            Logger::TYPE_DEBUG
        );

        if ($this->isMemoryLimit()) {
            $this->log(
                sprintf(
                    "Used Memory: %s Memory Limit: %s Max Script memory limit: %s",
                    size_format($usedMemory),
                    size_format($maxMemoryLimit),
                    size_format($scriptMemoryLimit)
                ),
                Logger::TYPE_ERROR
            );

            return true;
        }

        // Check if execution time is over threshold
        if ($this->isTimeLimit()) {
            $this->debugLog(
                sprintf(
                    "RESET TIME: current time: %s, Start Time: %d, exec time limit: %s",
                    $this->getRunningTime(),
                    WPStaging::$startTime,
                    $this->findExecutionTimeLimit()
                )
            );
            return true;
        }

        return false;
    }

    /**
     * @param string $msg
     * @param string $type
     */
    public function log($msg, $type = Logger::TYPE_INFO)
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->setFileName($this->getLogFilename());

        $this->logger->add($msg, $type);
    }

    /**
     * @return string
     */
    protected function getFilesIndexCacheFilePath(): string
    {
        return trailingslashit($this->cache->getPath()) . self::FILES_INDEX_KEY . '.' . Cache::FILE_EXTENSION;
    }

    /**
     * @return string
     */
    private function getLogFilename()
    {
        $uniqueId = $this->identifier->getIdentifier();
        // If job is not cloning i.e. updating, resetting, pushing
        if (!empty($this->options->mainJob) && $this->options->mainJob !== Job::STAGING) {
            return $this->options->mainJob . '_' . $uniqueId . '_' . date('Y-m-d', time());
        }

        // If job is cloning
        if (!empty($this->options->clone) && !empty($this->options->mainJob)) {
            return $this->options->mainJob . '_' . $uniqueId . '_' . $this->options->clone . '_' . date('Y-m-d', time());
        }

        if (empty($this->options->clone) && !empty($this->options->mainJob)) {
            return $this->options->mainJob . '_' . $uniqueId . '_unknown_clone_' . date('Y-m-d', time());
        }

        if (!empty($this->options->clone) && empty($this->options->mainJob)) {
            return 'unknown_job_' . $uniqueId . '_' .  $this->options->clone . '_' . date('Y-m-d', time());
        }

        return 'unknown_job_' . $uniqueId . '_' . date('Y-m-d', time());
    }

    /**
     * @param string $msg
     * @param string $type
     */
    public function debugLog($msg, $type = Logger::TYPE_INFO)
    {
        $this->logger->setFileName($this->getLogFilename());

        if (isset($this->settings->debugMode)) {
            $this->logger->add($msg, $type);
        }
    }

    /**
     * Throw an error message via json and stop further execution
     * @param string $message
     */
    public function returnException($message = '')
    {
        wp_die(
            json_encode(
                [
                    'job'     => isset($this->options->currentJob) ? $this->options->currentJob : '',
                    'status'  => false,
                    'message' => esc_html($message),
                    'error'   => true
                ]
            )
        );
    }

    /**
     * Is job running
     * @return bool
     */
    protected function isRunning()
    {
        if (!isset($this->options) || !isset($this->options->isRunning) || !isset($this->options->expiresAt)) {
            return false;
        }

        try {
            $now       = new DateTime();
            $expiresAt = new DateTime($this->options->expiresAt);
            return $this->options->isRunning === true && $now < $expiresAt;
        } catch (Exception $e) {
        }

        return false;
    }

    protected function isPro()
    {
        return defined('WPSTGPRO_VERSION');
    }

    /**
     * @return bool
     */
    protected function isMultisiteAndPro()
    {
        return $this->isPro() && is_multisite();
    }

    /**
     * @return bool
     */
    public function isNetworkClone()
    {
        if (!isset($this->options->networkClone)) {
            return false;
        }

        return $this->isMultisiteAndPro() && $this->options->networkClone;
    }

    /**
     * Should exclude wp-config file during clone update
     *
     * @return bool
     */
    public function excludeWpConfigDuringUpdate()
    {
        return $this->options->mainJob === self::UPDATE;
    }

    /**
     * Check if external database is used
     * @return bool
     */
    protected function isExternalDatabase()
    {
        return !(empty($this->options->databaseUser) && empty($this->options->databasePassword));
    }

    /**
     * @return bool
     */
    protected function isStagingDatabaseSameAsProductionDatabase()
    {
        if (empty($this->options->databaseUser) || empty($this->options->databaseServer) || empty($this->options->databaseDatabase)) {
            return true;
        }

        if ($this->options->databaseServer === DB_HOST && $this->options->databaseDatabase === DB_NAME) {
            return true;
        }

        $productionDb     = WPStaging::make('wpdb');
        $productionDbInfo = new WpDbInfo($productionDb);
        $productionServer = $productionDbInfo->getServer();

        $stagingDb     = new \wpdb($this->options->databaseUser, str_replace("\\\\", "\\", $this->options->databasePassword), $this->options->databaseDatabase, $this->options->databaseServer);
        $stagingDbInfo = new WpDbInfo($stagingDb);
        $stagingServer = $stagingDbInfo->getServer();

        if ($productionServer === $stagingServer && $this->options->databaseDatabase === DB_NAME) {
            return true;
        }

        return false;
    }

    /**
     * Is the current main job UPDATE or RESET
     *
     * @return bool
     */
    public function isUpdateOrResetJob(): bool
    {
        return isset($this->options->mainJob) && ($this->options->mainJob === self::RESET || $this->options->mainJob === self::UPDATE);
    }

    /**
     * @param string $jobName
     * @return void
     */
    protected function addJobSettingsToLogs(string $jobName = 'WP Staging Job')
    {
        $this->logger->add(sprintf('%s Settings', esc_html($jobName)), Logger::TYPE_INFO);
        $this->logger->writeSelectedTablesToLogs($this->options->tables);
        $this->logger->add('Excluded Directories', Logger::TYPE_INFO);
        foreach ($this->options->excludedDirectories as $directory) {
            $this->logger->add(sprintf('- %s', esc_html($directory)), Logger::TYPE_INFO_SUB);
        }

        if (!empty($this->options->excludeGlobRules)) {
            $this->logger->add('Exclude Global Rule', Logger::TYPE_INFO);

            foreach ($this->options->excludeGlobRules as $rule) {
                $excludeRule = explode(':', $rule);
                $ruleName = ucwords($excludeRule[0] ?? '');
                $ruleDescription = ucwords(str_replace('_', ' ', !empty($excludeRule[1]) ? $excludeRule[1] : ''));
                $this->logger->add(sprintf('- Exclude %s : %s', esc_html($ruleName), esc_html($ruleDescription)), Logger::TYPE_INFO_SUB);
            }
        }

        if (!empty($this->options->excludeSizeRules)) {
            $this->logger->add('Exclude Size Rule', Logger::TYPE_INFO);
            foreach ($this->options->excludeSizeRules as $rule) {
                $ruleDescription = ucwords(str_replace('_', ' ', !empty($rule) ? $rule : ''));
                $this->logger->add(sprintf('- Exclude Size : %s', esc_html($ruleDescription)), Logger::TYPE_INFO_SUB);
            }
        }


        $this->writeAdvancedSettingsToLogs();
        $this->logger->writeGlobalSettingsToLogs();
    }

    /**
     * @return void
     */
    private function writeAdvancedSettingsToLogs()
    {
        $this->logger->add('Advanced Settings', Logger::TYPE_INFO);

        if (isset($this->options->useNewAdminAccount)) {
            $this->logger->add(sprintf('- New Admin Account : %s', ($this->options->useNewAdminAccount ? 'True' : 'False')), Logger::TYPE_INFO_SUB);
            $this->logger->add(sprintf('- Email : %s', (!empty($this->options->adminEmail) ? $this->options->adminEmail : 'Not Set')), Logger::TYPE_INFO_SUB);
            $this->logger->add(sprintf('- Password : %s', (!empty($this->options->adminPassword) ? '**************' : 'Not Set')), Logger::TYPE_INFO_SUB);
        }

        $this->logger->add(sprintf('- Database Server : %s', (!empty($this->options->databaseServer) ? $this->options->databaseServer : 'Not Set')), Logger::TYPE_INFO_SUB);
        $this->logger->add(sprintf('- Database User : %s', (!empty($this->options->databaseUser) ? $this->options->databaseUser : 'Not Set')), Logger::TYPE_INFO_SUB);
        $this->logger->add(sprintf('- Database Password : %s', (!empty($this->options->databasePassword) ? '*****************' : 'Not Set')), Logger::TYPE_INFO_SUB);
        $this->logger->add(sprintf('- Database : %s', (!empty($this->options->databasePassword) ? $this->options->databaseDatabase : 'Not Set')), Logger::TYPE_INFO_SUB);
        $this->logger->add(sprintf('- Database Prefix: %s', (!empty($this->options->databasePrefix) ? $this->options->databasePrefix : 'Not Set')), Logger::TYPE_INFO_SUB);
        $this->logger->add(sprintf('- Database SSL: %s', ($this->options->databasePrefix ? 'True' : 'False')), Logger::TYPE_INFO_SUB);
        $this->logger->add(sprintf('- Clone Directory : %s', (!empty($this->options->cloneDir) ? $this->options->cloneDir : 'Not Set')), Logger::TYPE_INFO_SUB);
        $this->logger->add(sprintf('- Clone Host : %s', (!empty($this->options->cloneHostname) ? $this->options->cloneHostname : 'Not Set')), Logger::TYPE_INFO_SUB);
        $this->logger->add(sprintf('- Symlink Uploads Folder : %s', ($this->options->uploadsSymlinked ? 'True' : 'False')), Logger::TYPE_INFO_SUB);

        if (isset($this->options->isAutoUpdatePlugins)) {
            $this->logger->add(sprintf('- Auto Update Plugins : %s', ($this->options->isAutoUpdatePlugins ? 'True' : 'False')), Logger::TYPE_INFO_SUB);
        }

        if (isset($this->options->cronDisabled)) {
            $this->logger->add(sprintf('- Disable WP_CRON : %s', ($this->options->cronDisabled ? 'True' : 'False')), Logger::TYPE_INFO_SUB);
        }

        if (isset($this->options->emailsAllowed)) {
            $this->logger->add(sprintf('- Allow Emails Sending : %s', ($this->options->emailsAllowed ? 'True' : 'False')), Logger::TYPE_INFO_SUB);
        }

        if (isset($this->options->deletePluginsAndThemes)) {
            $this->logger->add(sprintf('- Clean Plugins/Themes : %s', ($this->options->deletePluginsAndThemes ? 'True' : 'False')), Logger::TYPE_INFO_SUB);
        }

        if (isset($this->options->deleteUploadsFolder)) {
            $this->logger->add(sprintf('- Clean Uploads : %s', ($this->options->deleteUploadsFolder ? 'True' : 'False')), Logger::TYPE_INFO_SUB);
        }

        if (isset($this->options->createBackupBeforePushing)) {
            $this->logger->add(sprintf('- Create database backup : %s', ($this->options->createBackupBeforePushing ? 'True' : 'False')), Logger::TYPE_INFO_SUB);
        }
    }
}
