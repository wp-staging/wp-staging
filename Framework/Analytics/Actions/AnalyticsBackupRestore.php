<?php

namespace WPStaging\Framework\Analytics\Actions;

use WPStaging\Framework\Analytics\AnalyticsEventDto;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;

class AnalyticsBackupRestore extends AnalyticsEventDto
{
    public function getEventAction()
    {
        return 'event_backup_restore';
    }

    public function enqueueStartEvent($eventId, $eventData)
    {
        if (!$eventData instanceof JobRestoreDataDto) {
            return;
        }

        $this->is_backup_database = $eventData->getBackupMetadata()->getIsExportingDatabase();
        $this->is_backup_plugins = $eventData->getBackupMetadata()->getIsExportingPlugins();
        $this->is_backup_themes = $eventData->getBackupMetadata()->getIsExportingThemes();
        $this->is_backup_uploads = $eventData->getBackupMetadata()->getIsExportingUploads();
        $this->is_backup_muplugins = $eventData->getBackupMetadata()->getIsExportingMuPlugins();
        $this->is_backup_wp_content = $eventData->getBackupMetadata()->getIsExportingOtherWpContentFiles();
        $this->database_size = $eventData->getBackupMetadata()->getDatabaseFileSize();
        $this->requirement_fail_reason = $eventData->getRequirementFailReason();
        $this->automated_backup = (int)$eventData->getBackupMetadata()->getIsAutomatedBackup(); // int to convert null to zero

        parent::enqueueStartEvent($eventId, $eventData);
    }
}
