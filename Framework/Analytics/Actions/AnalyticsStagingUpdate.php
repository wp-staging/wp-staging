<?php

namespace WPStaging\Framework\Analytics\Actions;

use WPStaging\Framework\Analytics\AnalyticsEventDto;

class AnalyticsStagingUpdate extends AnalyticsEventDto
{
    public $emails_allowed;
    public $delete_plugins_and_themes;
    public $delete_uploads_folder;
    public $backup_uploads_folder;

    public function getEventAction()
    {
        return 'event_staging_update';
    }

    public function enqueueStartEvent($eventId, $eventData)
    {
        $this->emails_allowed = !empty($eventData->emailsAllowed);
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
