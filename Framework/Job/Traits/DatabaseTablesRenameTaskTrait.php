<?php

namespace WPStaging\Framework\Job\Traits;

use Exception;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Backup\Service\Database\Exporter\ViewDDLOrder;
use WPStaging\Backup\Service\Database\Importer\TableViewsRenamer;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Framework\Analytics\AnalyticsConsent;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Database\TablesRenamer;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Traits\RestoresPreservedOptionsTrait;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Staging\Sites;
use WPStaging\Vendor\Psr\Log\LoggerInterface;





trait DatabaseTablesRenameTaskTrait
{
    use RestoresPreservedOptionsTrait;

 
    private $tableService;

 
    private $tablesRenamer;

 
    private $tableViewsRenamer;

 
    private $accessToken;

 
    private $siteInfo;

 
    protected $optionsToKeep = [];

 
    protected $optionsToRemove = [];

 
    protected $viewDDLOrder;

    public function __construct(SiteInfo $siteinfo, TablesRenamer $tablesRenamer, ViewDDLOrder $viewDDLOrder, TableService $tableService, TableViewsRenamer $tableViewsRenamer, AccessToken $accessToken, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->tableService      = $tableService;
        $this->tablesRenamer     = $tablesRenamer;
        $this->accessToken       = $accessToken;
        $this->viewDDLOrder      = $viewDDLOrder;
        $this->siteInfo          = $siteinfo;
        $this->tableViewsRenamer = $tableViewsRenamer;
    }






    abstract protected function getLicenseOptionsToKeep(): array;







    protected function getAutoloadedOptions()
    {
        global $wpdb;
        $suppress     = $wpdb->suppress_errors();
        $allOptionsDb = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE autoload = 'yes'");
        $wpdb->suppress_errors($suppress);

        $allOptions = [];
        foreach ((array)$allOptionsDb as $o) {
            $allOptions[] = $o->option_name;
        }

        return $allOptions;
    }







    protected function keepOptions()
    {
        $allOptions = $this->getAutoloadedOptions();

        $this->keepCurrentOptionValues(['wpstg_existing_clones_beta', Sites::STAGING_SITES_OPTION], $allOptions);
        $this->keepOption('wpstg_settings', get_option('wpstg_settings'), $allOptions);

        foreach ($this->getLicenseOptionsToKeep() as $optionName => $optionValue) {
            $this->keepOption($optionName, $optionValue, $allOptions);
        }

        $this->keepCurrentOptionValues([
            'wpstg_is_staging_site',
            BackupScheduler::OPTION_BACKUP_SCHEDULES,
            'blog_public',
            FinishBackupTask::OPTION_LAST_BACKUP,
            AnalyticsConsent::OPTION_NAME_ANALYTICS_HAS_CONSENT,
            AnalyticsConsent::OPTION_NAME_ANALYTICS_NOTICE_DISMISSED,
            AnalyticsConsent::OPTION_NAME_ANALYTICS_MODAL_DISMISSED,
            AnalyticsConsent::OPTION_NAME_ANALYTICS_REMIND_ME,
        ], $allOptions);

        $this->optionsToKeep = Hooks::callInternalHook(static::HOOK_KEEP_OPTIONS, [$this->optionsToKeep], $this->optionsToKeep);

        $this->keepAnalyticsEvents();
    }




    protected function setupRemoveOptions()
    {
        if (!$this->siteInfo->isStagingSite()) {
            $this->optionsToRemove[] = 'wpstg_is_staging_site';
        }
    }




    protected function preserveTransientOptions()
    {
        $transientToPreserve = [
            JobTransientCache::TRANSIENT_CURRENT_JOB,
        ];

        foreach ($transientToPreserve as $transient) {
            $this->tablesRenamer->preserveTmpOption('_transient_' . $transient);
            $this->tablesRenamer->preserveTmpOption('_transient_timeout_' . $transient);
        }
    }





    protected function renameViewReferences($tmpPrefix)
    {
        $views = $this->tablesRenamer->getViewsToBeRenamed();
        foreach ($views as $view) {
            $query = $this->tableService->getCreateViewQuery($this->tableService->getDatabase()->getPrefix() . $view);
            $query = str_replace($tmpPrefix, $this->tableService->getDatabase()->getPrefix(), $query);
            $this->viewDDLOrder->enqueueViewToBeWritten($this->tableService->getDatabase()->getPrefix() . $view, $query);
        }

        foreach ($this->viewDDLOrder->tryGetOrderedViews() as $tmpViewName => $viewQuery) {
            $this->tableViewsRenamer->renameViewReferences($viewQuery);
        }
    }






    protected function setupTask()
    {
        $this->accessToken->setIsCheckCapabilities(!$this->jobDataDto->getIsSyncRequest());
        if ($this->stepsDto->getTotal() > 0) {
            return;
        }

        $this->stepsDto->setTotal(3);
    }










    protected function renameImportedTables(string $operation, string $renamedVerb, $totalTablesToRename): bool
    {
        $this->setupTableRenamer();
        $this->tablesRenamer->setTaskDto($this->currentTaskDto);

        $result = $this->tablesRenamer->renameNonConflictingTables();
        if ($result === false && $this->tablesRenamer->getRenamedTables() === 0) {
            $this->logger->critical(sprintf('Could not %s non-conflicting tables. Contact support@wp-staging.com.', $operation));
            throw new Exception(sprintf('Could not %s non-conflicting tables.', $operation));
        }

        if ($result === false || $this->tablesRenamer->getIsNonConflictingTablesRenamingTaskExecuted()) {
            $this->currentTaskDto->nonConflictingTablesRenamed = $this->tablesRenamer->getNonConflictingTablesRenamed();
            $this->logger->info(sprintf('%s %d/%d tables.', $renamedVerb, $this->currentTaskDto->nonConflictingTablesRenamed, $totalTablesToRename));
            $this->setCurrentTaskDto($this->currentTaskDto);
            return false;
        }

        $result = $this->tablesRenamer->renameConflictingTables();

        $this->currentTaskDto->conflictingTablesRenamed = $this->tablesRenamer->getConflictingTablesRenamed();
        $tablesRenamed = $this->currentTaskDto->nonConflictingTablesRenamed + $this->currentTaskDto->conflictingTablesRenamed;
        $this->logger->info(sprintf('%s %d/%d tables.', $renamedVerb, $tablesRenamed, $totalTablesToRename));
        $this->setCurrentTaskDto($this->currentTaskDto);

        if ($result === false && $this->tablesRenamer->getRenamedTables() === 0) {
            $this->logger->critical('Could not rename any database table. Please contact support@wp-staging.com.');
            throw new Exception("Could not rename any database table.");
        }

        return $result !== false;
    }




    protected function logTablesRenamerErrors()
    {
        foreach ($this->tablesRenamer->getErrors() as $error) {
            $this->logger->warning($error);
        }
    }






    protected function upgradeWordPressDatabaseIfNeeded()
    {
        if (!file_exists(trailingslashit(ABSPATH) . 'wp-admin/includes/upgrade.php')) {
            $this->logger->warning('Could not upgrade WordPress database version as the wp-admin/includes/upgrade.php file does not exist.');
            return;
        }

        global $wpdb, $wp_db_version, $wp_current_db_version;
        require_once trailingslashit(ABSPATH) . 'wp-admin/includes/upgrade.php';

        $wp_current_db_version = (int)__get_option('db_version');
        if ($wp_db_version === $wp_current_db_version) {
            return;
        }

        $wpdb->suppress_errors();

        wp_upgrade();

        $this->logger->info(sprintf('WordPress database upgraded successfully from db version %s to %s.', $wp_current_db_version, $wp_db_version));
    }






    private function keepCurrentOptionValues(array $optionNames, array $autoloadedOptions)
    {
        foreach ($optionNames as $optionName) {
            $this->keepOption($optionName, get_option($optionName), $autoloadedOptions);
        }
    }







    private function keepOption(string $optionName, $optionValue, array $autoloadedOptions)
    {
        $this->optionsToKeep[] = [
            'name'     => $optionName,
            'value'    => $optionValue,
            'autoload' => in_array($optionName, $autoloadedOptions),
        ];
    }






    private function keepAnalyticsEvents()
    {
        global $wpdb;

        $analyticsEvents = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE `option_name` LIKE 'wpstg_analytics_event_%' LIMIT 0, 200");
        if (empty($analyticsEvents)) {
            return;
        }

        foreach ($analyticsEvents as $option) {
            $this->optionsToKeep[] = [
                'name'     => $option->option_name,
                'value'    => $option->option_value,
                'autoload' => false,
            ];
        }
    }
}
