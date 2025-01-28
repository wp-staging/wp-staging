<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use RuntimeException;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Backup\Ajax\Restore\PrepareRestore;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Backup\Task\FileRestoreTask;

/**
 * @todo register analytics event and cleaning here
 */
class StartRestoreTask extends RestoreTask
{
    /**
     * List of filters that are boolean
     * @var array<string,string>
     */
    const BOOLEAN_FILTERS = [
        CleanExistingMediaTask::FILTER_KEEP_EXISTING_MEDIA => 'Keep Existing Media',
        RestorePluginsTask::FILTER_KEEP_EXISTING_PLUGINS => 'Keep Existing Plugins',
        RestorePluginsTask::FILTER_REPLACE_EXISTING_PLUGINS => 'Replace Existing Plugins',
        RestoreThemesTask::FILTER_KEEP_EXISTING_THEMES => 'Keep Existing Themes',
        RestoreThemesTask::FILTER_REPLACE_EXISTING_THEMES => 'Replace Existing Themes',
        RestoreMuPluginsTask::FILTER_KEEP_EXISTING_MUPLUGINS => 'Keep Existing Mu-Plugins',
        RestoreMuPluginsTask::FILTER_REPLACE_EXISTING_MUPLUGINS => 'Replace Existing Mu-Plugins',
        RestoreLanguageFilesTask::FILTER_REPLACE_EXISTING_LANGUAGES => 'Replace Existing Languages',
        RestoreOtherFilesInWpContentTask::FILTER_KEEP_EXISTING_OTHER_FILES => 'Keep Existing Other Files',
    ];

    /**
     * List of filters that has string value
     * @var array<string,string>
     */
    const STRING_FILTERS = [
        PrepareRestore::CUSTOM_TMP_PREFIX_FILTER => 'Temporary Database Prefix',
    ];

    /**
     * List of filters that has array value
     * @var array<string,string>
     */
    const ARRAY_FILTERS = [
        RestoreTask::FILTER_EXCLUDE_BACKUP_PARTS                                    => 'Exclude Backup Parts',
        CleanExistingMediaTask::FILTER_EXCLUDE_MEDIA_DURING_CLEANUP                 => 'Exclude Media During Cleanup',
        RestorePluginsTask::FILTER_BACKUP_RESTORE_EXCLUDE_PLUGINS                   => 'Exclude Plugins (Deprecated)',
        RestoreOtherFilesInWpContentTask::FILTER_EXCLUDE_OTHER_FILES_DURING_RESTORE => 'Exclude Other Files (Deprecated)',
        FileRestoreTask::FILTER_EXCLUDE_FILES_DURING_RESTORE                        => 'Exclude Files',
    ];

    public static function getTaskName()
    {
        return 'backup_start_restore';
    }

    public static function getTaskTitle()
    {
        return 'Starting Restore';
    }

    public function execute()
    {
        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setTotal(1);
        }

        try {
            $this->logger->info('#################### Start Restore Job ####################');
            $this->logger->writeLogHeader();
            $this->logger->writeInstalledPluginsAndThemes();
            $this->logger->add(sprintf('Backup Format: %s', $this->jobDataDto->getBackupMetadata()->getIsBackupFormatV1() ? 'v1' : 'v2'), Logger::TYPE_INFO);
            $this->logger->info('Is Same Site Restore: ' . ($this->jobDataDto->getIsSameSiteBackupRestore() ? 'Yes' : 'No'));
            $this->writeRestoreFiltersUsed();
        } catch (RuntimeException $e) {
            $this->logger->critical($e->getMessage());

            $this->jobDataDto->setRequirementFailReason($e->getMessage());

            return $this->generateResponse(false);
        }

        return $this->generateResponse();
    }

    /**
     * @return void
     */
    protected function writeRestoreFiltersUsed()
    {
        $this->logger->info('Restore Related Filters:');
        foreach (self::BOOLEAN_FILTERS as $filterName => $filterText) {
            $filterValue = $this->getFilterValue($filterName, false);
            $filterValue = !is_bool($filterValue) ? SystemInfo::NOT_SET_LABEL : ($filterValue ? 'Yes' : 'No');
            $this->logger->add('- ' . $filterText . ': ' . $filterValue, Logger::TYPE_INFO_SUB);
        }

        foreach (self::STRING_FILTERS as $filterName => $filterText) {
            $this->normalizeAndAppendLogs($filterName, $filterText);
        }

        foreach (self::ARRAY_FILTERS as $filterName => $filterText) {
            $this->normalizeAndAppendLogs($filterName, $filterText, []);
        }
    }

    /**
     * @param string $filterName
     * @param mixed $defaultValue
     * @return mixed
     */
    private function getFilterValue(string $filterName, $defaultValue = null)
    {
        if (!has_filter($filterName)) {
            return SystemInfo::NOT_SET_LABEL;
        }

        return Hooks::applyFilters($filterName, $defaultValue);
    }

    /**
     * @param $filterName
     * @param $filterText
     * @param $defaultValue
     * @return void
     */
    private function normalizeAndAppendLogs($filterName, $filterText, $defaultValue = '')
    {
        $filterValue = $this->getFilterValue($filterName, $defaultValue);
        if (!is_array($filterValue)) {
            $this->logger->add('- ' . $filterText . ': ' . $filterValue, Logger::TYPE_INFO_SUB);
            return;
        }

        $this->logger->add('- ' . $filterText, Logger::TYPE_INFO_SUB);
        foreach ($filterValue as $value) {
            $this->logger->add('- ' . $value, Logger::TYPE_INFO_SUB);
        }
    }
}
