<?php

namespace WPStaging\Framework\Analytics\Actions;

use WPStaging\Framework\Analytics\AnalyticsEventDto;
use WPStaging\Pro\Backup\Dto\Job\JobExportDataDto;

class AnalyticsBackupCreate extends AnalyticsEventDto
{
    public function getEventAction()
    {
        return 'event_backup_create';
    }

    public function enqueueStartEvent($jobId, $eventData)
    {
        if (!$eventData instanceof JobExportDataDto) {
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
