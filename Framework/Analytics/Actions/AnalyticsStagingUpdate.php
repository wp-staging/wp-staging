<?php

namespace WPStaging\Framework\Analytics\Actions;

use WPStaging\Framework\Analytics\AnalyticsEventDto;

class AnalyticsStagingUpdate extends AnalyticsEventDto
{
    public $emails_allowed;
    public $delete_plugins_and_themes;
    public $delete_uploads_folder;
    public $backup_uploads_folder;
    public $staging_engine;

    public function getEventAction()
    {
        return 'event_staging_update';
    }

    public function enqueueStartEvent($eventId, $eventData)
    {
        $this->emails_allowed             = !empty($this->getEventDataValue($eventData, 'isEmailsAllowed'));
        $this->delete_plugins_and_themes = !empty($this->getEventDataValue(
            $eventData,
            'deletePluginsAndThemes',
            $this->getEventDataValue($eventData, 'isCleanPluginsThemes')
        ));
        $this->delete_uploads_folder      = !empty($this->getEventDataValue(
            $eventData,
            'deleteUploadsFolder',
            $this->getEventDataValue($eventData, 'isCleanUploads')
        ));
        $this->backup_uploads_folder      = !empty($this->getEventDataValue($eventData, 'backupUploadsFolder'));
        $this->staging_engine             = $this->getStagingEngine($eventData);

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
