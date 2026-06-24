<?php

namespace WPStaging\Framework\Analytics\Actions;

use WPStaging\Framework\Analytics\AnalyticsEventDto;

class AnalyticsStagingPush extends AnalyticsEventDto
{
    public $create_backup_before_pushing;
    public $delete_plugins_and_themes;
    public $delete_uploads_folder;
    public $backup_uploads_folder;
    public $staging_engine;

    public function getEventAction()
    {
        return 'event_staging_push';
    }

    public function enqueueStartEvent($eventId, $eventData)
    {
        $this->create_backup_before_pushing = !empty($this->getEventDataValue(
            $eventData,
            'createBackupBeforePushing',
            $this->getEventDataValue($eventData, 'isCreateDatabaseBackup')
        ));
        $this->delete_plugins_and_themes    = !empty($this->getEventDataValue(
            $eventData,
            'deletePluginsAndThemes',
            $this->getEventDataValue($eventData, 'isCleanPluginsThemes')
        ));
        $this->delete_uploads_folder        = !empty($this->getEventDataValue(
            $eventData,
            'deleteUploadsFolder',
            $this->getEventDataValue($eventData, 'isCleanUploads')
        ));
        $this->backup_uploads_folder        = !empty($this->getEventDataValue(
            $eventData,
            'backupUploadsFolder',
            $this->getEventDataValue($eventData, 'isBackupUploads')
        ));
        $this->staging_engine               = $this->getStagingEngine($eventData);

        parent::enqueueStartEvent($eventId, $eventData);
    }

    /**
     * Overriding so that it's easier to find specific usages;
     */
    public function enqueueFinishEvent($jobId, $eventData, $eventOverrides = [])
    {
        parent::enqueueFinishEvent($jobId, $eventData, $eventOverrides);
    }
}
