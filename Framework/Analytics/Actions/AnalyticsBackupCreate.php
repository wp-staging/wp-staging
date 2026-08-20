<?php

namespace WPStaging\Framework\Analytics\Actions;

use WPStaging\Framework\Analytics\AnalyticsEventDto;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;

class AnalyticsBackupCreate extends AnalyticsEventDto
{
 
    public $is_backup_database;

 
    public $is_backup_plugins;

 
    public $is_backup_themes;

 
    public $is_backup_uploads;

 
    public $is_backup_muplugins;

 
    public $is_backup_wp_content;

 
    public $is_backup_wp_root;

 
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

        $this->is_backup_database   = $eventData->getIsExportingDatabase();
        $this->is_backup_plugins    = $eventData->getIsExportingPlugins();
        $this->is_backup_themes     = $eventData->getIsExportingThemes();
        $this->is_backup_uploads    = $eventData->getIsExportingUploads();
        $this->is_backup_muplugins  = $eventData->getIsExportingMuPlugins();
        $this->is_backup_wp_content = $eventData->getIsExportingOtherWpContentFiles();
        $this->is_backup_wp_root    = $eventData->getIsExportingOtherWpRootFiles();
        $this->automated_backup     = (int)$eventData->getIsAutomatedBackup(); 

        parent::enqueueStartEvent($jobId, $eventData);
    }

    public function enqueueFinishEvent($jobId, $eventData, $eventOverrides = [])
    {
        parent::enqueueFinishEvent($jobId, null, [
            'filesystem_size'  => $eventData->getFilesystemSize(),
            'database_size'    => $eventData->getDatabaseFileSize(),
            'discovered_files' => (int)$eventData->getDiscoveredFiles(), 
        ]);
    }
}
