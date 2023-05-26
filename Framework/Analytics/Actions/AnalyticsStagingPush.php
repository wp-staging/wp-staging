<?php

namespace WPStaging\Framework\Analytics\Actions;

use WPStaging\Framework\Analytics\AnalyticsEventDto;

class AnalyticsStagingPush extends AnalyticsEventDto
{
    public $create_backup_before_pushing;
    public $delete_plugins_and_themes;
    public $delete_uploads_folder;
    public $backup_uploads_folder;

    public function getEventAction()
    {
        return 'event_staging_push';
    }

    public function enqueueStartEvent($eventId, $eventData)
    {
        $this->create_backup_before_pushing = !empty($eventData->createBackupBeforePushing);
        $this->delete_plugins_and_themes = !empty($eventData->deletePluginsAndThemes);
        $this->delete_uploads_folder = !empty($eventData->deleteUploadsFolder);
        $this->backup_uploads_folder = !empty($eventData->backupUploadsFolder);

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
