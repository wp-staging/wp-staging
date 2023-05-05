<?php

namespace WPStaging\Framework\Analytics\Actions;

use WPStaging\Framework\Analytics\AnalyticsEventDto;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;

class AnalyticsBackupCreate extends AnalyticsEventDto
{
    /** @var bool */
    public $is_backup_database;

    /** @var bool */
    public $is_backup_plugins;

    /** @var bool */
    public $is_backup_themes;

    /** @var bool */
    public $is_backup_uploads;

    /** @var bool */
    public $is_backup_muplugins;

    /** @var bool */
    public $is_backup_wp_content;

    /** @var int */
    public $automated_backup;

    public function getEventAction()
    {
        return 'event_backup_create';
    }

    public function enqueueStartEvent($jobId, $eventData)
    {
        if (!$eventData instanceof JobBackupDataDto) {
            return;
        }

        $this->is_backup_database = $eventData->getIsExportingDatabase();
        $this->is_backup_plugins = $eventData->getIsExportingPlugins();
        $this->is_backup_themes = $eventData->getIsExportingThemes();
        $this->is_backup_uploads = $eventData->getIsExportingUploads();
        $this->is_backup_muplugins = $eventData->getIsExportingMuPlugins();
        $this->is_backup_wp_content = $eventData->getIsExportingOtherWpContentFiles();
        $this->automated_backup = (int)$eventData->getIsAutomatedBackup(); // int to convert null to zero

        parent::enqueueStartEvent($jobId, $eventData);
    }

    public function enqueueFinishEvent($jobId, $eventData, $eventOverrides = [])
    {
        parent::enqueueFinishEvent($jobId, null, [
            'filesystem_size' => $eventData->getFilesystemSize(),
            'database_size' => $eventData->getDatabaseFileSize(),
            'discovered_files' => (int)$eventData->getDiscoveredFiles(), // int to convert null to zero
        ]);
    }
}
