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
use WPStaging\Framework\Database\ExternalDatabaseConfiguration;
use WPStaging\Framework\Interfaces\ShutdownableInterface;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Utils\Math;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Framework\Database\WpDbInfo;
use WPStaging\Framework\Security\UniqueIdentifier;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Staging\Sites;
use WPStaging\Staging\Service\StagingEngine;





abstract class Job implements ShutdownableInterface
{
    use ResourceTrait;




    const PUSH    = 'push';




    const STAGING = 'cloning';




    const RESET   = 'resetting';




    const UPDATE  = 'updating';





    const FILES_INDEX_KEY = 'clone_files_index';





    const CLONE_OPTIONS_KEY = 'clone_options';




    protected $cloneOptionCache;




    protected $filesIndexCache;




    protected $cache;




    protected $logger;




    protected $options;




    protected $settings;





    protected $baseUrl;

 
    protected $excludedTableService;

 
    protected $identifier;

 
    protected $utilsMath;

 
    protected $systemInfo;

 
    protected $externalDatabaseConfiguration;





    public function __construct()
    {
        $this->utilsMath = new Math();

        $this->excludedTableService = new ExcludedTables();
        $this->externalDatabaseConfiguration = new ExternalDatabaseConfiguration();

 
 
        $this->logger     = WPStaging::getInstance()->get("logger");
        $this->systemInfo = WPStaging::make(SystemInfo::class);
        $this->identifier = WPStaging::make(UniqueIdentifier::class);

        $this->setupCacheFiles();

 
        $this->options  = $this->cloneOptionCache->get();
 
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





    public function initialize()
    {
 
    }





    public function onWpShutdown()
    {
 
    }

    protected function setupCacheFiles()
    {
 
        $this->cloneOptionCache = WPStaging::make(Cache::class);
        $this->cloneOptionCache->setLifetime(-1); 
        $this->cloneOptionCache->setPath(WPStaging::getContentDir());
        $this->cloneOptionCache->setFileName(self::CLONE_OPTIONS_KEY);

 
        $this->filesIndexCache = WPStaging::make(Cache::class);
        $this->filesIndexCache->setLifetime(-1); 
        $this->filesIndexCache->setPath(WPStaging::getContentDir());
        $this->filesIndexCache->setFileName(self::FILES_INDEX_KEY);

 
        $this->cache = WPStaging::make(Cache::class);
        $this->cache->setLifetime(-1); 
        $this->cache->setPath(WPStaging::getContentDir());
    }






    public function saveOptions($options = null)
    {
 
        if ($options === null) {
            $options = $this->options;
        }

        if (!is_object($options)) {
            return false;
        }

        $now                = new DateTime();
        $options->expiresAt = $now->add(new DateInterval('P1D'))->format('Y-m-d H:i:s');

        if (!property_exists($options, 'jobIdentifier')) {
            $options->jobIdentifier = rand(0, 2147483647); 
        }

 
        $options = json_decode(json_encode($options));
        $result  = $this->cloneOptionCache->save($options);

        return $result !== false;
    }




    public function getOptions()
    {
        return $this->options;
    }




    protected function markLegacyStagingEngine()
    {
        $this->options->stagingEngine = StagingEngine::ENGINE_LEGACY;
        WPStaging::make(StagingEngine::class)->saveEngine(StagingEngine::ENGINE_LEGACY);
    }









    protected function loadLegacyExistingClones()
    {
        $existingClones                = get_option(Sites::STAGING_SITES_OPTION, []);
        $this->options->existingClones = is_array($existingClones)
            ? array_change_key_case($existingClones, CASE_LOWER)
            : [];
    }










    protected function initializeLegacyStagingRun($mainJob)
    {
        $this->options->mainJob     = $mainJob;
        $this->options->currentJob  = 'PreserveDataFirstStep';
        $this->options->currentStep = 0;
        $this->options->totalSteps  = 0;
        $this->options->job         = new stdClass();

        $this->options->clonedTables = [];

        if (!property_exists($this->options, 'excludedTables') || !is_array($this->options->excludedTables)) {
            $this->options->excludedTables = [];
        }

        if (!property_exists($this->options, 'totalFiles')) {
            $this->options->totalFiles = 0;
        }

        if (!property_exists($this->options, 'totalFileSize')) {
            $this->options->totalFileSize = 0;
        }

        if (!property_exists($this->options, 'copiedFiles')) {
            $this->options->copiedFiles = 0;
        }

        if (!property_exists($this->options, 'includedDirectories')) {
            $this->options->includedDirectories = [];
        }

        if (!property_exists($this->options, 'includedExtraDirectories')) {
            $this->options->includedExtraDirectories = [];
        }

        if (!property_exists($this->options, 'excludedDirectories')) {
            $this->options->excludedDirectories = [];
        }

        if (!property_exists($this->options, 'extraDirectories')) {
            $this->options->extraDirectories = [];
        }

        if (!property_exists($this->options, 'scannedDirectories')) {
            $this->options->scannedDirectories = [];
        }

        if (!property_exists($this->options, 'root')) {
            $this->options->root = str_replace(["\\", '/'], DIRECTORY_SEPARATOR, ABSPATH);
        }

        $this->markLegacyStagingEngine();
    }





    protected function time()
    {
        $time = microtime();
        $time = explode(' ', $time);
        $time = (float)$time[1] + (float)$time[0];
        return $time;
    }




    public function isOverThreshold()
    {
 
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





    public function log($msg, $type = Logger::TYPE_INFO)
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->setFileName($this->getLogFilename());

        $this->logger->add($msg, $type);
    }




    protected function getFilesIndexCacheFilePath(): string
    {
        return trailingslashit($this->cache->getPath()) . self::FILES_INDEX_KEY . '.' . Cache::FILE_EXTENSION;
    }




    private function getLogFilename()
    {
        $uniqueId = $this->identifier->getIdentifier();
 
        if (!empty($this->options->mainJob) && $this->options->mainJob !== Job::STAGING) {
            return $this->options->mainJob . '_' . $uniqueId . '_' . date('Y-m-d', time());
        }

 
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





    public function debugLog($msg, $type = Logger::TYPE_INFO)
    {
        $this->logger->setFileName($this->getLogFilename());

        if (isset($this->settings->debugMode)) {
            $this->logger->add($msg, $type);
        }
    }





    public function returnException($message = '')
    {
        wp_die(
            json_encode(
                [
                    'job'     => isset($this->options->currentJob) ? $this->options->currentJob : '',
                    'status'  => false,
                    'message' => esc_html($message),
                    'error'   => true,
                ]
            )
        );
    }





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




    protected function isMultisiteAndPro()
    {
        return $this->isPro() && is_multisite();
    }




    public function isNetworkClone()
    {
        if (!isset($this->options->networkClone)) {
            return false;
        }

        return $this->isMultisiteAndPro() && $this->options->networkClone;
    }






    public function excludeWpConfigDuringUpdate()
    {
        return $this->options->mainJob === self::UPDATE;
    }





    protected function isExternalDatabase()
    {
        return $this->externalDatabaseConfiguration->isEnabled($this->options);
    }




    protected function isStagingDatabaseSameAsProductionDatabase()
    {
        if (!$this->isExternalDatabase()) {
            return true;
        }

        if (!$this->externalDatabaseConfiguration->hasConnectionTarget($this->options)) {
            return false;
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






    public function isUpdateOrResetJob(): bool
    {
        return isset($this->options->mainJob) && ($this->options->mainJob === self::RESET || $this->options->mainJob === self::UPDATE);
    }





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

        if (isset($this->options->isCronEnabled)) {
            $this->logger->add(sprintf('- Enable WP_CRON : %s', ($this->options->isCronEnabled ? 'True' : 'False')), Logger::TYPE_INFO_SUB);
        }

        if (isset($this->options->isWooSchedulerEnabled)) {
            $this->logger->add(sprintf('- Enable WooCommerce Scheduler : %s', ($this->options->isWooSchedulerEnabled ? 'True' : 'False')), Logger::TYPE_INFO_SUB);
        }

        if (isset($this->options->isEmailsAllowed)) {
            $this->logger->add(sprintf('- Allow Emails Sending : %s', ($this->options->isEmailsAllowed ? 'True' : 'False')), Logger::TYPE_INFO_SUB);
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
